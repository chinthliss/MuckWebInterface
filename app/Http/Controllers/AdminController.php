<?php

namespace App\Http\Controllers;

use App\AccountHistoryManager;
use App\Admin\LogManager;
use App\MuckWebInterfaceUserProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminController extends Controller
{
    public function showHome(): View
    {
        return view('admin.home');
    }

    public function showAccount(string $accountId, AccountHistoryManager $historyManager): View
    {
        $user = User::find($accountId);
        if (!$user) abort(404);

        return view('admin.account', [
            'account' => $user->toArray('all'),
            'history' => $historyManager->getHistoryFor($user)
        ]);
    }

    public function showAccountBrowser(): View
    {
        return view('admin.accounts');
    }

    public function processAccountChange(Request $request, string $accountId): string
    {
        if (!$request->has('operation')) abort(400);
        $target = User::find($accountId);
        if (!$target) abort(404);

        /** @var User $reporter */
        $reporter = auth()->user();
        $reporterCharacter = $reporter->getCharacter();

        $operation = $request->get('operation');
        switch ($operation) {
            case 'lock':
                $target->setIsLocked(true);
                break;

            case 'unlock':
                $target->setIsLocked(false);
                break;

            case 'addAccountNote':
                $note = $request->get('note');
                if (!$note) abort(400, 'No note text provided.');
                if (!$reporterCharacter || !$reporterCharacter->isAdmin()) abort(400, 'You need to be logged on as an admin character, so that the note can be attributed to them.');
                $target->addAccountNote($reporterCharacter->name, $note);
                break;

            default:
                abort(400, 'Unrecognized operation requested.');
        }
        return 'OK';
    }

    public function findAccounts(Request $request, MuckWebInterfaceUserProvider $userProvider): array
    {
        $searchCharacterName = $request->input('character');
        $searchEmail = $request->input('email');
        $searchCreatedAfter = $request->has('createdAfter') ? new Carbon($request->input('createdAfter')) : null;
        $searchCreatedBefore = $request->has('createdBefore') ? new Carbon($request->input('createdBefore')) : null;

        if (!$searchCharacterName && !$searchEmail && !$searchCreatedAfter && !$searchCreatedBefore)
            abort(400, 'No criteria specified');

        $results = [];

        if ($searchCharacterName) {
            $nextResults = $userProvider->searchByCharacterName($searchCharacterName);
            $results = $nextResults;
        }

        if ($searchEmail) {
            $nextResults = $userProvider->searchByEmail($searchEmail);
            if (count($results))
                $results = array_intersect($results, $nextResults);
            else
                $results = $nextResults;
        }

        if ($searchCreatedAfter || $searchCreatedBefore) {
            $nextResults = $userProvider->searchByCreationDateRange($searchCreatedAfter, $searchCreatedBefore);
            if (count($results))
                $results = array_intersect($results, $nextResults);
            else
                $results = $nextResults;
        }

        return array_map(function ($account) {
            return $account->toArray('admin');
        }, $results);
    }

    #region Site Logs
    public function showLogViewer(): View
    {
        return view('admin.logviewer')->with([
            'dates' => LogManager::getDates()
        ]);
    }

    public function getLogForDate(string $date): BinaryFileResponse
    {
        return response()->file(LogManager::getLogFilePathForDate($date));
    }

    #endregion Site Logs

    public function showCommunicationLogsViewer(): View
    {
        return view('admin.communicationlogviewer');
    }

    public function getCommunicationLogs(Request $request): Response
    {
        $values = $request->validate([
            'type' => 'required',
            'from' => 'sometimes',
            'to' => 'required'
        ], [
            'type.required' => 'You need to select a type.',
            'to.required' => 'To must be entered'
        ]);
        $type = $values['type'];
        $from = $values['from'];
        $to = $values['to'];
        // Page requires both to be set, everything else just needs 'to'.
        if ($type == 'page' && is_null($from)) {
            throw ValidationException::withMessages([
                'from' => 'From must be entered',
            ]);
        }

        // Log request
        /** @var User $user */
        $user = $request->user();
        Log::info("CommunicationLogs: Request by $user: $type, with values from=$from and to=$to");

        // And now the actual query
        $query = DB::table('log_communication')
            ->select(['when_at', 'from_dbref', 'from_name', 'to_dbref', 'to_name', 'content'])
            ->where('game', '=', config('muck.code'))
            ->where('type', '=', $type);

        if (str_starts_with($from, '#')) {
            $fromColumn = 'dbref';
            $fromValue = intval(substr($from, 1, strlen($from)));
        } else {
            $fromColumn = 'name';
            $fromValue = $from;
        }

        if (str_starts_with($to, '#')) {
            $toColumn = 'dbref';
            $toValue = intval(substr($to, 1, strlen($to)));
        } else {
            $toColumn = 'name';
            $toValue = $to;
        }

        if ($type !== 'page') {
            // Non pages just look at 'To'
            $query->where('to_' . $toColumn, '=', $toValue);
        } else {
            // Pages are a pain as to get both sides of a conversation we need to flip 'from' and 'to'
            $query->where(function ($query) use ($fromColumn, $fromValue) {
                $query->where('from_' . $fromColumn, '=', $fromValue)
                    ->orWhere('to_' . $fromColumn, '=', $fromValue);
            });
            $query->where(function ($query) use ($toColumn, $toValue) {
                $query->where('from_' . $toColumn, '=', $toValue)
                    ->orWhere('to_' . $toColumn, '=', $toValue);
            });
            // They both need to have something in though
            $query->whereNotNull(['from_dbref', 'to_dbref']);
        }

        // Special addition - if we're doing pages from someone to anybody we only show the outgoing ones
        if (!$to && $type == 'page') {
            $query->whereNull('to_dbref');
        }

        $rows = $query->get();
        return response(json_encode($rows));
    }
}
