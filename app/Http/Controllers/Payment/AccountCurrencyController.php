<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Payment\CardPaymentManager;
use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransactionManager;
use App\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AccountCurrencyController extends Controller
{
    private const minimumAmountUsd = 5;

    #region Transactions
    public function showTransactions(PaymentTransactionManager $transactionManager): View
    {
        /** @var User $user */
        $user = auth()->user();

        $transactions = [];
        foreach ($transactionManager->getTransactionsFor($user->id()) as $transaction) {
            $transactions[] = $transaction->toArray();
        }
        return view('account.transactions')->with([
            'transactions' => $transactions
        ]);
    }

    public function showTransaction(PaymentTransactionManager $transactionManager, string $id): View
    {
        /** @var User $user */
        $user = auth()->user();
        $transaction = $transactionManager->getTransaction($id);

        if (!$transaction) abort(404);
        if ($transaction->accountId != $user->id()) abort(403);

        return view('account.transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    //Returns {accountCurrencyUsd, items, recurringInterval}

    /**
     * @throws Exception
     */
    private function parseBaseRequest(Request $request): array
    {
        $amountUsd = (int)$request->input('amountUsd', 0);
        if ($amountUsd && $amountUsd < self::minimumAmountUsd)
            throw new Exception('Amount specified was beneath minimum amount of $' . self::minimumAmountUsd . '.');

        $items = $request->has('items') ? $request['items'] : [];
        if (!$items && !$amountUsd)
            throw new Exception("Transaction has no value.<br/>" .
                "You need to specify either an amount or select item(s).");

        $recurringInterval = $request->has('recurringInterval') ? (int)$request['recurringInterval'] : null;

        return [
            'accountCurrencyUsd' => $amountUsd,
            'items' => $items,
            'recurringInterval' => $recurringInterval
        ];
    }

    public function newCardTransaction(Request                   $request, CardPaymentManager $cardPaymentManager,
                                       PaymentTransactionManager $transactionManager): array
    {
        /** @var User $user */
        $user = auth()->user();

        $cardId = $request->input('cardId');
        $card = $cardId ? $cardPaymentManager->getCardFor($user, $cardId)
            : $cardPaymentManager->getDefaultCardFor($user);
        if (!$card) abort(400, "Default card couldn't be found or isn't valid.");

        $baseDetails = null;
        try {
            $baseDetails = $this->parseBaseRequest($request);
        } catch (Exception $e) {
            abort(400, $e->getMessage());
        }

        if ($baseDetails['recurringInterval']) abort(400, "A transaction can't have an interval.");

        return $transactionManager->createTransactionForDirectSupport(
            $user, 'authorizenet', $card->id,
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['items']
        )->toTransactionOfferArray();
    }

    /**
     * @param Request $request
     * @param PaymentTransactionManager $transactionManager
     * @return array
     */
    public function newPayPalTransaction(Request $request, PaymentTransactionManager $transactionManager): array
    {
        /** @var User $user */
        $user = auth()->user();

        $baseDetails = null;
        try {
            $baseDetails = $this->parseBaseRequest($request);
        } catch (Exception $e) {
            abort(400, $e->getMessage());
        }

        if ($baseDetails['recurringInterval']) abort(400, "A transaction can't have an interval.");

        return $transactionManager->createTransactionForDirectSupport(
            $user, "paypal", "paypal_unattributed",
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['items']
        )->toTransactionOfferArray();

    }


    /**
     * @throws Exception
     */
    public function acceptTransaction(Request $request, PaymentTransactionManager $transactionManager): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $transactionId = $request->input('id');

        if (!$transactionId || !$user) abort(403);

        $transaction = $transactionManager->getTransaction($transactionId);

        if ($transaction->accountId != $user->id()
            || $transaction->paid()
            || !$transaction->open()) abort(403);

        // If this is a PayPal transaction, we create an order with them and redirect user to their approval
        if ($transaction->vendor == 'paypal') {
            throw new Exception("TODO: Reimplement PayPal transaction accepting");
            /*
            $payPalManager = resolve(PayPalManager::class);
            try {
                $approvalUrl = $payPalManager->startPayPalOrderFor($user, $transaction);
                return redirect($approvalUrl);
            } catch (Exception $e) {
                Log::info("Error during starting paypal payment: " . $e);
                abort(500);
            }
            */
        }

        //Otherwise we attempt to charge the card
        if (!$transaction->paid()) {
            if ($transaction->vendor !== 'paypal') {
                try {
                    $transactionManager->chargeTransaction($transaction);
                } catch (Exception $e) {
                    Log::info("Error during account currency card payment: " . $e);
                }
            }
        }

        if ($transaction->paid()) {
            $transactionManager->fulfillAndCloseTransaction($transaction);
        } else
            $transactionManager->closeTransaction($transaction, 'vendor_refused');
        return redirect()->route('account.transaction', [
            'id' => $transactionId
        ]);
    }

    /**
     * @throws Exception
     */
    public function declineTransaction(Request $request, PaymentTransactionManager $transactionManager): string
    {
        /** @var User $user */
        $user = auth()->user();

        $transactionId = $request->input('id');

        if (!$transactionId || !$user) abort(403);

        $transaction = $transactionManager->getTransaction($transactionId);

        if ($transaction->accountId != $user->id() || !$transaction->open()) abort(403);

        $transactionManager->closeTransaction($transaction, 'user_declined');
        return "Transaction Declined";
    }



    #endregion Transactions

    #region Subscriptions

    /**
     * @throws Exception
     */
    public function acceptSubscription(Request $request, PaymentSubscriptionManager $subscriptionManager): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptionId = $request->input('id');

        if (!$subscriptionId || !$user) abort(403);

        $subscription = $subscriptionManager->getSubscription($subscriptionId);

        if ($subscription->accountId != $user->id() || !$subscription->open()) abort(403);

        // If this is a PayPal transaction, we move over to their process
        if ($subscription->vendor == 'paypal') {
            throw new Exception("TODO: Reimplement PayPal subscription accepting");
            /*
            $payPalManager = resolve(PayPalManager::class);
            try {
                $approvalUrl = $payPalManager->startPayPalSubscriptionFor($user, $subscription);
                return redirect($approvalUrl);
            } catch (Exception $e) {
                Log::info("Error during starting paypal subscription: " . $e);
                abort(500);
            }
            */
        }

        //Otherwise we mark it as active and attempt to process
        $subscriptionManager->setSubscriptionAsActive($subscription);
        $subscriptionManager->processSubscription($subscription);
        return redirect()->route('account.subscription', [
            'id' => $subscription->id
        ]);
    }

    /**
     * @throws Exception
     */
    public function declineSubscription(Request $request, PaymentSubscriptionManager $subscriptionManager): string
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptionId = $request->input('id');

        if (!$subscriptionId || !$user) abort(403);

        $subscription = $subscriptionManager->getSubscription($subscriptionId);

        if ($subscription->accountId != $user->id() || !($subscription->status == 'approval_pending')) abort(403);

        $subscriptionManager->closeSubscription($subscription, 'user_declined');
        return "Subscription Declined";
    }

    /**
     * @throws Exception
     */
    public function cancelSubscription(Request $request, PaymentSubscriptionManager $subscriptionManager): string
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptionId = $request->input('id');

        if (!$subscriptionId || !$user) abort(403);

        $subscription = $subscriptionManager->getSubscription($subscriptionId);

        if ($subscription->accountId != $user->id()) abort(403);

        $subscriptionManager->closeSubscription($subscription, 'cancelled');
        return "Subscription Cancelled.";

    }

    public function showSubscription(PaymentSubscriptionManager $subscriptionManager,
                                     PaymentTransactionManager $transactionManager, string $id)
    {
        /** @var User $user */
        $user = auth()->user();
        $subscription = $subscriptionManager->getSubscription($id);

        if (!$subscription) abort(404);
        if ($subscription->accountId != $user->id() && !$user->isSiteAdmin()) abort(403);

        $transactions = [];
        foreach ($transactionManager->getTransactionsForSubscription($subscription) as $transaction) {
            $transactions[] = $transaction->toArray();
        }

        return view('account.subscription')->with([
            'subscription' => $subscription->toArray(),
            'transactions' => $transactions
        ]);
    }

    #endregion Subscriptions

    #region Admin Functionality

    public function adminShowTransactions(): View
    {
        return view('admin.transactions');
    }

    public function adminGetTransactions(PaymentTransactionManager $transactionManager): array
    {
        return array_map(function ($transaction) {
            return $transaction->toArray();
        }, $transactionManager->getAllTransactions());
    }

    public function adminShowTransaction(PaymentTransactionManager $transactionManager, string $id): View
    {
        $transaction = $transactionManager->getTransaction($id);

        if (!$transaction) abort(404);

        return view('admin.transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    #endregion Admin Functionality
}
