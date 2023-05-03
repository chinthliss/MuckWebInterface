<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Payment\PaymentTransactionManager;
use App\User;
use Illuminate\View\View;

class AccountCurrencyController extends Controller
{

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


}
