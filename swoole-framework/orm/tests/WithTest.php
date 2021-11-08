<?php


namespace EasySwoole\ORM\Tests;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Tests\models\TestA;
use EasySwoole\ORM\Tests\models\TestB;
use EasySwoole\ORM\Tests\models\TestC;
use EasySwoole\ORM\Tests\models\TestRelationModel;
use EasySwoole\ORM\Tests\models\TestUserListModel;
use EasySwoole\ORM\Utility\Schema\Table;
use PHPUnit\Framework\TestCase;


class WithTest extends TestCase
{
    /**
     * @var $connection Connection
     */
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $config = new Config(MYSQL_CONFIG);
        $config->setReturnCollection(true);
        $this->connection = new Connection($config);
        DbManager::getInstance()->addConnection($this->connection);
        $connection = DbManager::getInstance()->getConnection();
        $this->assertTrue($connection === $this->connection);
    }

    public function testAdd()
    {
        $test_user_model = TestRelationModel::create();
        $test_user_model->name = 'gaobinzhan';
        $test_user_model->age = 20;
        $test_user_model->addTime = ($time = date('Y-m-d H:i:s'));
        $test_user_model->state = 2;
        $test_user_model->save();
        $user_list = TestUserListModel::create();
        $user_list->name = 'gaobinzhan';
        $user_list->age = 20;
        $user_list->addTime = $time;
        $user_list->state = 1;
        $user_list->save();

        $test_user_model = TestRelationModel::create();
        $test_user_model->name = 'gaobinzhan1';
        $test_user_model->age = 20;
        $test_user_model->addTime = ($time = date('Y-m-d H:i:s'));
        $test_user_model->state = 2;
        $test_user_model->save();
        $user_list = TestUserListModel::create();
        $user_list->name = 'gaobinzhan1';
        $user_list->age = 20;
        $user_list->addTime = $time;
        $user_list->state = 1;
        $user_list->save();
    }

    public function testHasOne()
    {
        $model = TestRelationModel::create();
        $result = $model->with(['hasOneEqName' => 'gaobinzhan1'])->where(['name' => 'gaobinzhan'])->get()->toArray(null, false);
        $this->assertEmpty($result['hasOneEqName']);
        $result = $model->with(['hasOneEqName' => 'gaobinzhan'])->where(['name' => 'gaobinzhan'])->get()->toArray(null, false);
        $this->assertEquals($result['hasOneEqName']['name'], 'gaobinzhan');

        $result = $model->where(['name' => 'gaobinzhan'])->get()->toArray(null, false);
        $this->assertFalse(isset($result['hasOneEqName']));
    }

    public function testHasMany()
    {
        $user_list = TestUserListModel::create();
        $user_list->name = 'gaobinzhan';
        $user_list->age = 20;
        $user_list->addTime = date('Y-m-d H:i:s');
        $user_list->state = 1;
        $user_list->save();

        $result = TestRelationModel::create()->with(['hasManyEqName' => ['gaobinzhan1']])->where(['name' => 'gaobinzhan'])->all()->toArray(null, false);
        $this->assertFalse(isset($result[0]['hasManyEqName']));

        $result = TestRelationModel::create()->with(['hasManyEqName' => ['gaobinzhan']])->where(['name' => 'gaobinzhan'])->all()->toArray(null, false);
        $this->assertEquals(count($result[0]['hasManyEqName']), 2);
        $this->assertEquals($result[0]['hasManyEqName'][0]['name'], 'gaobinzhan');
    }

    public function testCreateTable()
    {

        // 主表a b副表 c为b副表
        $sql = "SHOW TABLES LIKE 'test_a';";
        $query = new QueryBuilder();
        $query->raw($sql);
        $data = $this->connection->defer()->query($query);

        if (empty($data->getResult())) {
            $tableDDL = new Table('test_a');
            $tableDDL->colInt('id', 11)->setIsPrimaryKey()->setIsAutoIncrement();
            $tableDDL->colVarChar('a_name', 255);
            $tableDDL->setIfNotExists();
            $sql = $tableDDL->__createDDL();
            $query->raw($sql);
            $data = $this->connection->defer()->query($query);
            $this->assertTrue($data->getResult());
        }

        $sql = "SHOW TABLES LIKE 'test_b';";
        $query = new QueryBuilder();
        $query->raw($sql);
        $data = $this->connection->defer()->query($query);

        if (empty($data->getResult())) {
            $tableDDL = new Table('test_b');
            $tableDDL->colInt('id', 11)->setIsPrimaryKey()->setIsAutoIncrement();
            $tableDDL->colInt('a_id', 11);
            $tableDDL->colVarChar('b_name', 255);
            $tableDDL->setIfNotExists();
            $sql = $tableDDL->__createDDL();
            $query->raw($sql);
            $data = $this->connection->defer()->query($query);
            $this->assertTrue($data->getResult());
        }

        $sql = "SHOW TABLES LIKE 'test_c';";
        $query = new QueryBuilder();
        $query->raw($sql);
        $data = $this->connection->defer()->query($query);

        if (empty($data->getResult())) {
            $tableDDL = new Table('test_c');
            $tableDDL->colInt('id', 11)->setIsPrimaryKey()->setIsAutoIncrement();
            $tableDDL->colInt('b_id', 11);
            $tableDDL->colVarChar('c_name', 255);
            $tableDDL->setIfNotExists();
            $sql = $tableDDL->__createDDL();
            $query->raw($sql);
            $data = $this->connection->defer()->query($query);
            $this->assertTrue($data->getResult());
        }
    }

    public function testWithToArray()
    {
        $aId = TestA::create()->data(['a_name' => 'testA'])->save();
        $bId1 = TestB::create()->data(['a_id' => $aId, 'b_name' => 'testB1'])->save();
        $bId2 = TestB::create()->data(['a_id' => $aId, 'b_name' => 'testB2'])->save();
        $cId1 = TestC::create()->data(['b_id' => $bId1, 'c_name' => 'testC1'])->save();
        $cId2 = TestC::create()->data(['b_id' => $bId1, 'c_name' => 'testC2'])->save();
        $cId3 = TestC::create()->data(['b_id' => $bId2, 'c_name' => 'testC1'])->save();
        $cId4 = TestC::create()->data(['b_id' => $bId2, 'c_name' => 'testC2'])->save();

        $testAM = TestA::create();

        // hasOne
        // get
        $result = $testAM->with('hasOneList')->get($aId)->toArray(null, false);
        $this->assertEquals('testB1-bar-b', $result['hasOneList']['b_name']);
        $this->assertEquals('testC2', $result['hasOneList']['c_name']);
        $result = $testAM->with('hasOneList')->get($aId)->toArray(null, null);
        $this->assertTrue(!isset($result['hasOneList']));
        $result = $testAM->with(['hasOneJoinList'], false)->field('*,test_a.id as aid,test_b.id as bid')->join('test_b', 'test_b.a_id = test_a.id', 'left')->get()->toArray(false, false);
        $this->assertEquals($aId, $result['aid']);
        $this->assertEquals($bId2, $result['bid']);
        $this->assertEquals($cId3, $result['hasOneJoinList']['cid']);

        // all
        $result = $testAM->with('hasOneList')->all()->toArray(null, false);
        $this->assertEquals('testB2-bar-b', $result[0]['hasOneList']['b_name']);
        $this->assertEquals('testC1', $result[0]['hasOneList']['c_name']);
        $result = $testAM->with('hasOneList')->all()->toArray(null, null);
        $this->assertTrue(!isset($result[0]['hasOneList']));
        $result = $testAM->with(['hasOneJoinList'], false)->field('*,test_a.id as aid,test_b.id as bid')->join('test_b', 'test_b.a_id = test_a.id', 'left')->all()->toArray(false, false);
        $this->assertEquals($aId, $result[0]['aid']);
        $this->assertEquals($bId2, $result[0]['bid']);
        $this->assertEquals($cId4, $result[0]['hasOneJoinList']['cid']);

        // hasMany
        // get
        $result = $testAM->with('hasManyList')->get($aId)->toArray(null, false);
        $this->assertEquals('testB1-bar-b', $result['hasManyList'][0]['b_name']);
        $this->assertEquals('testC2', $result['hasManyList'][0]['c_name']);
        $result = $testAM->with('hasManyList')->get($aId)->toArray(null, null);
        $this->assertTrue(!isset($result['hasManyList']));
        $result = $testAM->with(['hasManyJoinList'], false)->field('*,test_a.id as aid,test_b.id as bid')->join('test_b', 'test_b.a_id = test_a.id', 'left')->get()->toArray(false, false);
        $this->assertEquals($aId, $result['aid']);
        $this->assertEquals($bId2, $result['bid']);
        $this->assertEquals($cId3, $result['hasManyJoinList'][0]['cid']);
        // all
        $result = $testAM->with('hasManyList')->all()->toArray(null, false);
        $this->assertEquals('testB1-bar-b', $result[0]['hasManyList'][0]['b_name']);
        $this->assertEquals('testC2', $result[0]['hasManyList'][0]['c_name']);
        $result = $testAM->with('hasManyList')->all()->toArray(null, null);
        $this->assertTrue(!isset($result[0]['hasManyList']));
        $result = $testAM->with(['hasManyJoinList'], false)->field('*,test_a.id as aid,test_b.id as bid')->join('test_b', 'test_b.a_id = test_a.id', 'left')->all()->toArray(false, false);
        $this->assertEquals($aId, $result[0]['aid']);
        $this->assertEquals($bId2, $result[0]['bid']);
        $this->assertEquals($cId3, $result[0]['hasManyJoinList'][0]['cid']);
    }

    public function testDeleteAll()
    {
        $res = TestRelationModel::create()->destroy(null, true);
        $this->assertIsInt($res);
        $res = TestUserListModel::create()->destroy(null, true);
        $this->assertIsInt($res);
        $res = TestA::create()->destroy(null, true);
        $this->assertIsInt($res);
        $res = TestB::create()->destroy(null, true);
        $this->assertIsInt($res);
        $res = TestC::create()->destroy(null, true);
        $this->assertIsInt($res);
    }
}