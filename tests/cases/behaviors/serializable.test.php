<?php

App::import('Core', 'Model');

/**
 * Serialized Session
 */
class SerializedSession extends CakeTestModel {
	public $alias = 'Session';
	public $useTable = 'sessions';
	public $actsAs = array('Utils.Serializable' => array('fields' => array('data')));
}

/**
 * Serialized Post
 */
class SerializedContent extends CakeTestModel {
	public $alias = 'Content';
	public $useTable = 'contents';
	public $actsAs = array('Utils.Serializable' => array('fields' => array('title', 'body')));
}

class SerializableTestCase extends CakeTestCase {

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.session', 'plugin.utils.content');

/**
 * SerializableBehavior Settings
 *
 * @var mixed
 */
	public $settings = array();

/**
 * startTest
 *
 * @return void
 * @access public
 */
	public function startTest($method) {
		parent::startTest($method);

		$this->Session = ClassRegistry::init('SerializedSession');
		$this->Content = ClassRegistry::init('SerializedContent');

		$this->settings =& $this->Session->Behaviors->Serializable->settings;
	}

/**
 * endTest
 *
 * @return void
 * @access public
 */
	public function endTest() {
		unset($this->Session, $this->Content, $this->settings);
	}

	public function testEmptyDataValue() {
		$record = array('Session' => array('id' => 1, 'expires' => 1000));
		$this->Session->create($record);
		$result = $this->Session->save();

		$record = array('Session' => array('id' => 1, 'data' => array(), 'expires' => 1000));
		$data = $this->Session->find('first', array('conditions' => array('id' => 1)));
		$this->assertEqual($data, $record);
	}

	public function testJsonEmptyArrayDataValue() {
		$this->settings['Session']['engine'] = 'json';
		$record = array('Session' => array('id' => 1, 'data' => array(), 'expires' => 1000));
		$this->Session->create($record);
		$result = $this->Session->save();
		$this->assertEqual($result, array('Session' => array('id' => 1, 'data' => '[]', 'expires' => 1000)));

		$data = $this->Session->find('first', array('conditions' => array('id' => 1)));
		$this->assertEqual($data, $record);
	}

	public function testSerializeEmptyArrayDataValue() {
		$this->settings['Session']['engine'] = 'serialize';
		$record = array('Session' => array('id' => 1, 'data' => array(), 'expires' => 1000));
		$this->Session->create($record);
		$result = $this->Session->save();
		$this->assertEqual($result, array('Session' => array('id' => 1, 'data' => 'a:0:{}', 'expires' => 1000)));

		$data = $this->Session->find('first', array('conditions' => array('id' => 1)));
		$this->assertEqual($data, $record);
	}


	public function testJsonOneElementInData() {
		$this->settings['Session']['engine'] = 'json';
		$record = array('Session' => array('id' => 1, 'data' => array('k' => 'value'), 'expires' => 1000));
		$this->Session->create($record);
		$result = $this->Session->save();
		$this->assertEqual($result, array('Session' => array('id' => 1, 'data' => '{"k":"value"}', 'expires' => 1000)));

		$data = $this->Session->find('first', array('conditions' => array('id' => 1)));
		$this->assertEqual($data, $record);
	}

	public function testSerializeOneElementInData() {
		$this->settings['Session']['engine'] = 'serialize';
		$record = array('Session' => array('id' => 1, 'data' => array('k' => 'value'), 'expires' => 1000));
		$this->Session->create($record);
		$result = $this->Session->save();
		$this->assertEqual($result, array('Session' => array('id' => 1, 'data' => 'a:1:{s:1:"k";s:5:"value";}', 'expires' => 1000)));

		$data = $this->Session->find('first', array('conditions' => array('id' => 1)));
		$this->assertEqual($data, $record);
	}


	public function testSaveAllSerialized() {
		$this->settings['Session']['engine'] = 'json';
		$record = array('Session' => array(array('id' => 1, 'data' => array('k' => 'value'), 'expires' => 1000), array('id' => 2, 'data' => array('k' => 'value'), 'expires' => 1000)));
		$result = $this->Session->saveAll($record['Session']);
		$this->assertEqual($result, 1);

		$data = $this->Session->find('all', array('conditions' => array('id' => array(1, 2))));
		$this->assertEqual(Set::classicExtract($data, '{n}.Session'), $record['Session']);
	}


	public function testJsonSeveralDataFields() {
		$this->settings['Content']['engine'] = 'json';
		$record = array('Content' => array('id' => 1, 'title' => array('k' => 'value'), 'body' => array('k' => 'value'), 'published' => 'N'));
		$this->Content->create($record);
		$result = $this->Content->save();
		unset($result['Content']['updated'], $result['Content']['created']);
		$this->assertEqual($result, array('Content' => array('id' => 1, 'title' => '{"k":"value"}', 'body' => '{"k":"value"}', 'published' => 'N')));

		$data = $this->Content->find('first', array('conditions' => array('id' => 1), 'fields' => array('id', 'title', 'body', 'published')));
		$this->assertEqual($data, $record);
	}

	public function testSerializeSeveralDataFields() {
		$this->settings['Content']['engine'] = 'serialize';
		$record = array('Content' => array('id' => 1, 'title' => array('k' => 'value'), 'body' => array('k' => 'value'), 'published' => 'N'));
		$this->Content->create($record);
		$result = $this->Content->save();
		unset($result['Content']['updated'], $result['Content']['created']);
		$this->assertEqual($result, array('Content' => array('id' => 1, 'title' => 'a:1:{s:1:"k";s:5:"value";}', 'body' => 'a:1:{s:1:"k";s:5:"value";}', 'published' => 'N')));

		$data = $this->Content->find('first', array('conditions' => array('id' => 1), 'fields' => array('id', 'title', 'body', 'published')));
		$this->assertEqual($data, $record);
	}


	public function testErrorIsSerializedData() {
		$this->Session->Behaviors->detach('Serializable');
		$record = array('Session' => array('id' => 1, 'data' => 'error text', 'expires' => 1000));
		$this->Session->create($record);
		$result = $this->Session->save();

		$this->Session->Behaviors->attach('Utils.Serializable', array('fields' => array('data')));
		$record = array('Session' => array('id' => 1, 'data' => array(), 'expires' => 1000));
		$data = $this->Session->find('first', array('conditions' => array('id' => 1)));
		$this->assertEqual($data, $record);
	}
}
?>