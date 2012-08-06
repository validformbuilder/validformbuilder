<?php



/**
 * This class defines the structure of the 'dbform' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 * @package    propel.generator.validformtest.map
 */
class DBFormTableMap extends TableMap
{

	/**
	 * The (dot-path) name of this class
	 */
	const CLASS_NAME = 'validformtest.map.DBFormTableMap';

	/**
	 * Initialize the table attributes, columns and validators
	 * Relations are not initialized by this method since they are lazy loaded
	 *
	 * @return     void
	 * @throws     PropelException
	 */
	public function initialize()
	{
		// attributes
		$this->setName('dbform');
		$this->setPhpName('DBForm');
		$this->setClassname('DBForm');
		$this->setPackage('validformtest');
		$this->setUseIdGenerator(true);
		// columns
		$this->addColumn('NAME', 'Name', 'VARCHAR', true, 255, null);
		$this->addColumn('SERIALIZED', 'Serialized', 'LONGVARCHAR', true, null, null);
		$this->addColumn('IS_COMPLETE', 'IsComplete', 'TINYINT', true, null, 0);
		$this->addColumn('CREATED_AT', 'CreatedAt', 'TIMESTAMP', false, null, null);
		$this->addColumn('UPDATED_AT', 'UpdatedAt', 'TIMESTAMP', false, null, null);
		$this->addPrimaryKey('ID', 'Id', 'BIGINT', true, null, null);
		// validators
	} // initialize()

	/**
	 * Build the RelationMap objects for this table relationships
	 */
	public function buildRelations()
	{
	} // buildRelations()

	/**
	 *
	 * Gets the list of behaviors registered for this table
	 *
	 * @return array Associative array (name => parameters) of behaviors
	 */
	public function getBehaviors()
	{
		return array(
			'timestampable' => array('create_column' => 'created_at', 'update_column' => 'updated_at', ),
			'auto_add_pk' => array('name' => 'id', 'autoIncrement' => 'true', 'type' => 'BIGINT', ),
		);
	} // getBehaviors()

} // DBFormTableMap
