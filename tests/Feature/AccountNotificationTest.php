<?php


use App\AccountNotificationManager;
use App\Notifications\MuckWebInterfaceNotification;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Factories\UserFactory;

class AccountNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifying_without_character_or_game_works()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->id()
        ]);
    }

    public function test_notifying_with_game_but_without_character_works()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyUser($user, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->id(),
            'game_code' => config('muck.muck_code')
        ]);
    }

    public function test_notifying_with_game_and_character_works()
    {
        //Need to use the seeded entries for this test
        $this->seed();
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => '1234']);

        /** @var User $user */
        $user = auth()->user();

        $character = $user->getCharacter();
        MuckWebInterfaceNotification::NotifyCharacter($user, $character, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->id(),
            'game_code' => config('muck.muck_code'),
            'character_dbref' => $character->dbref
        ]);
    }

    public function test_user_gets_notification()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(1, $notifications);
        $count = $transactionManager->getNotificationCountFor($user);
        $this->assertCount(1, $count);
    }

        /**
     * @depends test_user_gets_notification
     */
    public function testUserDoesNotGetAnotherUsersNotifications()
    {
        $user = UserFactory::create();
        $otherUser = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($otherUser, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications);
    }

    /**
     * @depends test_user_gets_notification
     */
    public function test_user_does_not_get_game_specific_notifications_from_another_game()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyUser($user, 'Test', 50);
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications);
    }

    /**
     * @depends test_user_gets_notification
     */
    public function test_user_can_delete_notification()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $notification = $notifications[0];
        $response = $this->delete(route('account.notifications.api') . '/' . $notification['id']);
        $response->assertSuccessful();

        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications, 'Notification was not deleted');
    }

    public function test_user_can_delete_all_notifications()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test 2');
        $notificationManager = resolve(AccountNotificationManager::class);
        $this->delete(route('account.notifications.api') . '/all');

        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications, 'All notifications were not deleted');
    }

    /**
     * @depends test_user_gets_notification
     */
    public function test_user_can_not_delete_other_users_notifications()
    {
        $user = UserFactory::create();
        $otherUser = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($otherUser, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $notification = $notifications[0];

        $response = $this->actingAs($user)->delete(route('account.notifications.api') . '/' . $notification['id']);
        $response->assertUnauthorized();

        $notifications = $transactionManager->getNotificationsFor($otherUser);
        $this->assertCount(1, $notifications);
    }

}
