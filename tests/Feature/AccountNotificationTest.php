<?php


use App\AccountNotificationManager;
use App\Notifications\MuckWebInterfaceNotification;
use App\User;
use Illuminate\Notifications\Events\NotificationSent;
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
        $notificationManager = resolve(AccountNotificationManager::class);
        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(1, $notifications);
        $count = $notificationManager->getUnreadNotificationsCountFor($user);
        $this->assertEquals(1, $count);
    }

    /**
     * @depends test_user_gets_notification
     */
    public function testUserDoesNotGetAnotherUsersNotifications()
    {
        $user = UserFactory::create();
        $otherUser = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($otherUser, 'Test');
        $notificationManager = resolve(AccountNotificationManager::class);
        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications);
    }

    /**
     * @depends test_user_gets_notification
     */
    public function test_user_does_not_get_game_specific_notifications_from_another_game()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyUser($user, 'Test', 50);
        $notificationManager = resolve(AccountNotificationManager::class);
        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications);
    }

    /**
     * @depends test_user_gets_notification
     */
    public function test_user_can_delete_notification()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $notificationManager = resolve(AccountNotificationManager::class);
        $notifications = $notificationManager->getNotificationsFor($user);
        $notification = $notifications[0];
        $response = $this->actingAs($user)->delete(route('notifications.api') . '/' . $notification['id']);
        $response->assertSuccessful();

        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications, 'Notification was not deleted');
    }

    public function test_user_can_delete_all_notifications()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test 2');
        $notificationManager = resolve(AccountNotificationManager::class);
        $response = $this->actingAs($user)->delete(route('notifications.api', ['highestId' => PHP_INT_MAX]));
        $response->assertSuccessful();

        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications, 'All notifications were not deleted');
    }

    /**
     * @depends test_user_can_delete_all_notifications
     */
    public function test_deleting_all_user_notifications_does_not_delete_unseen_notifications()
    {
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $notificationManager = resolve(AccountNotificationManager::class);
        $notifications = $notificationManager->getNotificationsFor($user);
        $notification = $notifications[0];

        MuckWebInterfaceNotification::NotifyAccount($user, 'Test 2');

        $this->actingAs($user)->delete(route('notifications.api', ['highestId' => $notification['id']]));

        $notifications = $notificationManager->getNotificationsFor($user);
        $this->assertCount(1, $notifications, 'All notifications were deleted');
    }

    /**
     * @depends test_user_can_delete_notification
     */
    public function test_user_can_not_delete_other_users_notification()
    {
        $user = UserFactory::create();
        $otherUser = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($otherUser, 'Test');
        $notificationManager = resolve(AccountNotificationManager::class);
        $notifications = $notificationManager->getNotificationsFor($otherUser);
        $notification = $notifications[0];

        $response = $this->actingAs($user)->delete(route('notifications.api') . '/' . $notification['id']);
        $response->assertUnauthorized();

        $notifications = $notificationManager->getNotificationsFor($otherUser);
        $this->assertCount(1, $notifications);
    }

    /**
     * @depends test_user_can_delete_all_notifications
     */
    public function test_user_can_not_delete_all_other_users_notifications()
    {
        $user = UserFactory::create();
        $otherUser = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($otherUser, 'Test');
        $notificationManager = resolve(AccountNotificationManager::class);

        $response = $this->actingAs($user)->delete(route('notifications.api', ['highestId' => PHP_INT_MAX]));
        $response->assertSuccessful(); // Should be successful since it only deletes the user's notifications

        $notifications = $notificationManager->getNotificationsFor($otherUser);
        $this->assertCount(1, $notifications);
    }

    /**
     * @depends test_user_gets_notification
     */
    public function test_muck_notification_event_sends_after_notification()
    {
        Event::fake();
        $user = UserFactory::create();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        Event::assertDispatched(NotificationSent::class, 1);
    }

}
