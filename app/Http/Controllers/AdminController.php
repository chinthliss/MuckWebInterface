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
            'to' => 'sometimes'
        ], [
            'type.required' => 'You need to select a type.'
        ]);
        $type = $values['type'];
        $from = $values['from'];
        $to = $values['to'];
        // Page requires either 'to' or 'from' set, everything else just needs to.
        if ($type == 'page') {
            if (is_null($from) and is_null($to)) {
                throw ValidationException::withMessages([
                    'from' => 'Either From or To must be entered',
                    'to' => 'Either From or To must be entered'
                ]);
            }
        } else if (is_null($to)) throw ValidationException::withMessages(['to' => 'To must be entered.']);

        // Log request
        /** @var User $user */
        $user = $request->user();
        Log::info("CommunicationLog request by $user: $type, with values from=$from and to=$to");

        // And now the actual query
        $query = DB::table('log_communication')
            ->select(['when_at', 'from_dbref', 'from_name', 'to_dbref', 'to_name', 'content'])
            ->where('type', $type);

        // From
        if ($from) {
            if (str_starts_with($from, '#')) {
                $query->where('from_dbref', '=', intval(substr($from, 1, strlen($from))));
            } else {
                $query->where('from_name', '=', $from);
            }
        }

        // To
        if ($to) {
            if (str_starts_with($to, '#')) {
                $query->where('to_dbref', '=', intval(substr($to, 1, strlen($to))));
            } else {
                $query->where('to_name', '=', $to);
            }
        }

        $rows = $query->get();
        return response(json_encode($rows));
    }
}
