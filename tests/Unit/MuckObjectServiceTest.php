<?php


use App\Muck\MuckConnection;
use App\Muck\MuckDbref;
use App\Muck\MuckObjectService;
use App\Muck\MuckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Database\Factories\MuckObjectFactory;

class MuckObjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private MuckObjectService $muckObjectService;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('muck.code', 123);
        $this->muckObjectService = $this->app->make(MuckObjectService::class);
    }

    public function test_getByDbref()
    {
        $object = MuckObjectFactory::createPlayer('MuckObjectTest');

        //Test with valid object
        $object = $this->muckObjectService->getByDbref($object->dbref);
        $this->assertNotNull($object, 'Failed to retrieve any object');
        $this->assertEquals('MuckObjectTest', $object->name, "Name wasn't correct when retrieving the test character.");

        //Test with invalid object
        $object = $this->muckObjectService->getByDbref(88234);
        $this->assertNull($object, "An object was returned when testing with invalid details");

    }

    public function test_getByName()
    {
        $validDbref = MuckObjectFactory::createPlayer('MuckObjectTest');

        //Test with valid name
        $object = $this->muckObjectService->getByPlayerName('MuckObjectTest');
        $this->assertNotNull($object, 'Failed to retrieve any object');
        $this->assertEquals($validDbref->dbref, $object->dbref, "Dbref wasn't correct when retrieving the test character.");

        //Test with invalid name
        $object = $this->muckObjectService->getByPlayerName('NoSuchCharacterExists');
        $this->assertNull($object, "An object was returned when testing with invalid details");

    }

    public function test_getByMuckObjectId()
    {
        MuckObjectFactory::createPlayer('MuckObjectTest');
        $id = DB::table('muck_objects')->value('id');
        $this->assertNotNull($id, "GetByMuckObjectId  test failed to fake a valid object.");
        $object = $this->muckObjectService->getByMuckObjectId($id);
        $this->assertNotNull($object, "GetByMuckObjectId failed retrieving created id of $id.");
    }

    public function test_GetMuckObjectIdFor_returns_new_id_if_appropriate()
    {

        // Test with a new object
        $object = new MuckDbref(7655, 'New', 't', Carbon::now());
        $id = $this->muckObjectService->getMuckObjectIdFor($object);
        $this->assertNotNull($id, "An ID wasn't returned for the new object");

        //Test repeating with the new object gets the same result
        $idSecond = $this->muckObjectService->getMuckObjectIdFor($object);
        $this->assertEquals($id, $idSecond, "Second request didn't give same ID.");
    }

}
