<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Payment\PaymentTransactionManager;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class AccountCurrencyController extends Controller
{

    public function showTransactions(): View
    {
        return view('account.transactions');
    }

    public function showTransaction(PaymentTransactionManager $transactionManager, string $id): View
    {
        /** @var User $user */
        $user = auth()->user();
        $transaction = $transactionManager->getTransaction($id);

        if (!$transaction) abort(404);
        if ($transaction->accountId != $user->id() && !$user->isSiteAdmin()) abort(403);

        return view('account.transaction')->with([
            'transaction' => $transaction->toArray()
        ]);

    }

}
