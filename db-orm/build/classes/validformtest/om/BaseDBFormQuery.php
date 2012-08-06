<?php


/**
 * Base class that represents a query for the 'dbform' table.
 *
 * 
 *
 * @method     DBFormQuery orderByName($order = Criteria::ASC) Order by the name column
 * @method     DBFormQuery orderBySerialized($order = Criteria::ASC) Order by the serialized column
 * @method     DBFormQuery orderByIsComplete($order = Criteria::ASC) Order by the is_complete column
 * @method     DBFormQuery orderByCreatedAt($order = Criteria::ASC) Order by the created_at column
 * @method     DBFormQuery orderByUpdatedAt($order = Criteria::ASC) Order by the updated_at column
 * @method     DBFormQuery orderById($order = Criteria::ASC) Order by the id column
 *
 * @method     DBFormQuery groupByName() Group by the name column
 * @method     DBFormQuery groupBySerialized() Group by the serialized column
 * @method     DBFormQuery groupByIsComplete() Group by the is_complete column
 * @method     DBFormQuery groupByCreatedAt() Group by the created_at column
 * @method     DBFormQuery groupByUpdatedAt() Group by the updated_at column
 * @method     DBFormQuery groupById() Group by the id column
 *
 * @method     DBFormQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     DBFormQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     DBFormQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     DBForm findOne(PropelPDO $con = null) Return the first DBForm matching the query
 * @method     DBForm findOneOrCreate(PropelPDO $con = null) Return the first DBForm matching the query, or a new DBForm object populated from the query conditions when no match is found
 *
 * @method     DBForm findOneByName(string $name) Return the first DBForm filtered by the name column
 * @method     DBForm findOneBySerialized(string $serialized) Return the first DBForm filtered by the serialized column
 * @method     DBForm findOneByIsComplete(int $is_complete) Return the first DBForm filtered by the is_complete column
 * @method     DBForm findOneByCreatedAt(string $created_at) Return the first DBForm filtered by the created_at column
 * @method     DBForm findOneByUpdatedAt(string $updated_at) Return the first DBForm filtered by the updated_at column
 * @method     DBForm findOneById(string $id) Return the first DBForm filtered by the id column
 *
 * @method     array findByName(string $name) Return DBForm objects filtered by the name column
 * @method     array findBySerialized(string $serialized) Return DBForm objects filtered by the serialized column
 * @method     array findByIsComplete(int $is_complete) Return DBForm objects filtered by the is_complete column
 * @method     array findByCreatedAt(string $created_at) Return DBForm objects filtered by the created_at column
 * @method     array findByUpdatedAt(string $updated_at) Return DBForm objects filtered by the updated_at column
 * @method     array findById(string $id) Return DBForm objects filtered by the id column
 *
 * @package    propel.generator.validformtest.om
 */
abstract class BaseDBFormQuery extends ModelCriteria
{
	
	/**
	 * Initializes internal state of BaseDBFormQuery object.
	 *
	 * @param     string $dbName The dabase name
	 * @param     string $modelName The phpName of a model, e.g. 'Book'
	 * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
	 */
	public function __construct($dbName = 'validformtest', $modelName = 'DBForm', $modelAlias = null)
	{
		parent::__construct($dbName, $modelName, $modelAlias);
	}

	/**
	 * Returns a new DBFormQuery object.
	 *
	 * @param     string $modelAlias The alias of a model in the query
	 * @param     Criteria $criteria Optional Criteria to build the query from
	 *
	 * @return    DBFormQuery
	 */
	public static function create($modelAlias = null, $criteria = null)
	{
		if ($criteria instanceof DBFormQuery) {
			return $criteria;
		}
		$query = new DBFormQuery();
		if (null !== $modelAlias) {
			$query->setModelAlias($modelAlias);
		}
		if ($criteria instanceof Criteria) {
			$query->mergeWith($criteria);
		}
		return $query;
	}

	/**
	 * Find object by primary key.
	 * Propel uses the instance pool to skip the database if the object exists.
	 * Go fast if the query is untouched.
	 *
	 * <code>
	 * $obj  = $c->findPk(12, $con);
	 * </code>
	 *
	 * @param     mixed $key Primary key to use for the query
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    DBForm|array|mixed the result, formatted by the current formatter
	 */
	public function findPk($key, $con = null)
	{
		if ($key === null) {
			return null;
		}
		if ((null !== ($obj = DBFormPeer::getInstanceFromPool((string) $key))) && !$this->formatter) {
			// the object is alredy in the instance pool
			return $obj;
		}
		if ($con === null) {
			$con = Propel::getConnection(DBFormPeer::DATABASE_NAME, Propel::CONNECTION_READ);
		}
		$this->basePreSelect($con);
		if ($this->formatter || $this->modelAlias || $this->with || $this->select
		 || $this->selectColumns || $this->asColumns || $this->selectModifiers
		 || $this->map || $this->having || $this->joins) {
			return $this->findPkComplex($key, $con);
		} else {
			return $this->findPkSimple($key, $con);
		}
	}

	/**
	 * Find object by primary key using raw SQL to go fast.
	 * Bypass doSelect() and the object formatter by using generated code.
	 *
	 * @param     mixed $key Primary key to use for the query
	 * @param     PropelPDO $con A connection object
	 *
	 * @return    DBForm A model object, or null if the key is not found
	 */
	protected function findPkSimple($key, $con)
	{
		$sql = 'SELECT `NAME`, `SERIALIZED`, `IS_COMPLETE`, `CREATED_AT`, `UPDATED_AT`, `ID` FROM `dbform` WHERE `ID` = :p0';
		try {
			$stmt = $con->prepare($sql);
			$stmt->bindValue(':p0', $key, PDO::PARAM_INT);
			$stmt->execute();
		} catch (Exception $e) {
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), $e);
		}
		$obj = null;
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$obj = new DBForm();
			$obj->hydrate($row);
			DBFormPeer::addInstanceToPool($obj, (string) $row[0]);
		}
		$stmt->closeCursor();

		return $obj;
	}

	/**
	 * Find object by primary key.
	 *
	 * @param     mixed $key Primary key to use for the query
	 * @param     PropelPDO $con A connection object
	 *
	 * @return    DBForm|array|mixed the result, formatted by the current formatter
	 */
	protected function findPkComplex($key, $con)
	{
		// As the query uses a PK condition, no limit(1) is necessary.
		$criteria = $this->isKeepQuery() ? clone $this : $this;
		$stmt = $criteria
			->filterByPrimaryKey($key)
			->doSelect($con);
		return $criteria->getFormatter()->init($criteria)->formatOne($stmt);
	}

	/**
	 * Find objects by primary key
	 * <code>
	 * $objs = $c->findPks(array(12, 56, 832), $con);
	 * </code>
	 * @param     array $keys Primary keys to use for the query
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    PropelObjectCollection|array|mixed the list of results, formatted by the current formatter
	 */
	public function findPks($keys, $con = null)
	{
		if ($con === null) {
			$con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_READ);
		}
		$this->basePreSelect($con);
		$criteria = $this->isKeepQuery() ? clone $this : $this;
		$stmt = $criteria
			->filterByPrimaryKeys($keys)
			->doSelect($con);
		return $criteria->getFormatter()->init($criteria)->format($stmt);
	}

	/**
	 * Filter the query by primary key
	 *
	 * @param     mixed $key Primary key to use for the query
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterByPrimaryKey($key)
	{
		return $this->addUsingAlias(DBFormPeer::ID, $key, Criteria::EQUAL);
	}

	/**
	 * Filter the query by a list of primary keys
	 *
	 * @param     array $keys The list of primary key to use for the query
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterByPrimaryKeys($keys)
	{
		return $this->addUsingAlias(DBFormPeer::ID, $keys, Criteria::IN);
	}

	/**
	 * Filter the query on the name column
	 *
	 * Example usage:
	 * <code>
	 * $query->filterByName('fooValue');   // WHERE name = 'fooValue'
	 * $query->filterByName('%fooValue%'); // WHERE name LIKE '%fooValue%'
	 * </code>
	 *
	 * @param     string $name The value to use as filter.
	 *              Accepts wildcards (* and % trigger a LIKE)
	 * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterByName($name = null, $comparison = null)
	{
		if (null === $comparison) {
			if (is_array($name)) {
				$comparison = Criteria::IN;
			} elseif (preg_match('/[\%\*]/', $name)) {
				$name = str_replace('*', '%', $name);
				$comparison = Criteria::LIKE;
			}
		}
		return $this->addUsingAlias(DBFormPeer::NAME, $name, $comparison);
	}

	/**
	 * Filter the query on the serialized column
	 *
	 * Example usage:
	 * <code>
	 * $query->filterBySerialized('fooValue');   // WHERE serialized = 'fooValue'
	 * $query->filterBySerialized('%fooValue%'); // WHERE serialized LIKE '%fooValue%'
	 * </code>
	 *
	 * @param     string $serialized The value to use as filter.
	 *              Accepts wildcards (* and % trigger a LIKE)
	 * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterBySerialized($serialized = null, $comparison = null)
	{
		if (null === $comparison) {
			if (is_array($serialized)) {
				$comparison = Criteria::IN;
			} elseif (preg_match('/[\%\*]/', $serialized)) {
				$serialized = str_replace('*', '%', $serialized);
				$comparison = Criteria::LIKE;
			}
		}
		return $this->addUsingAlias(DBFormPeer::SERIALIZED, $serialized, $comparison);
	}

	/**
	 * Filter the query on the is_complete column
	 *
	 * Example usage:
	 * <code>
	 * $query->filterByIsComplete(1234); // WHERE is_complete = 1234
	 * $query->filterByIsComplete(array(12, 34)); // WHERE is_complete IN (12, 34)
	 * $query->filterByIsComplete(array('min' => 12)); // WHERE is_complete > 12
	 * </code>
	 *
	 * @param     mixed $isComplete The value to use as filter.
	 *              Use scalar values for equality.
	 *              Use array values for in_array() equivalent.
	 *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
	 * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterByIsComplete($isComplete = null, $comparison = null)
	{
		if (is_array($isComplete)) {
			$useMinMax = false;
			if (isset($isComplete['min'])) {
				$this->addUsingAlias(DBFormPeer::IS_COMPLETE, $isComplete['min'], Criteria::GREATER_EQUAL);
				$useMinMax = true;
			}
			if (isset($isComplete['max'])) {
				$this->addUsingAlias(DBFormPeer::IS_COMPLETE, $isComplete['max'], Criteria::LESS_EQUAL);
				$useMinMax = true;
			}
			if ($useMinMax) {
				return $this;
			}
			if (null === $comparison) {
				$comparison = Criteria::IN;
			}
		}
		return $this->addUsingAlias(DBFormPeer::IS_COMPLETE, $isComplete, $comparison);
	}

	/**
	 * Filter the query on the created_at column
	 *
	 * Example usage:
	 * <code>
	 * $query->filterByCreatedAt('2011-03-14'); // WHERE created_at = '2011-03-14'
	 * $query->filterByCreatedAt('now'); // WHERE created_at = '2011-03-14'
	 * $query->filterByCreatedAt(array('max' => 'yesterday')); // WHERE created_at > '2011-03-13'
	 * </code>
	 *
	 * @param     mixed $createdAt The value to use as filter.
	 *              Values can be integers (unix timestamps), DateTime objects, or strings.
	 *              Empty strings are treated as NULL.
	 *              Use scalar values for equality.
	 *              Use array values for in_array() equivalent.
	 *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
	 * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterByCreatedAt($createdAt = null, $comparison = null)
	{
		if (is_array($createdAt)) {
			$useMinMax = false;
			if (isset($createdAt['min'])) {
				$this->addUsingAlias(DBFormPeer::CREATED_AT, $createdAt['min'], Criteria::GREATER_EQUAL);
				$useMinMax = true;
			}
			if (isset($createdAt['max'])) {
				$this->addUsingAlias(DBFormPeer::CREATED_AT, $createdAt['max'], Criteria::LESS_EQUAL);
				$useMinMax = true;
			}
			if ($useMinMax) {
				return $this;
			}
			if (null === $comparison) {
				$comparison = Criteria::IN;
			}
		}
		return $this->addUsingAlias(DBFormPeer::CREATED_AT, $createdAt, $comparison);
	}

	/**
	 * Filter the query on the updated_at column
	 *
	 * Example usage:
	 * <code>
	 * $query->filterByUpdatedAt('2011-03-14'); // WHERE updated_at = '2011-03-14'
	 * $query->filterByUpdatedAt('now'); // WHERE updated_at = '2011-03-14'
	 * $query->filterByUpdatedAt(array('max' => 'yesterday')); // WHERE updated_at > '2011-03-13'
	 * </code>
	 *
	 * @param     mixed $updatedAt The value to use as filter.
	 *              Values can be integers (unix timestamps), DateTime objects, or strings.
	 *              Empty strings are treated as NULL.
	 *              Use scalar values for equality.
	 *              Use array values for in_array() equivalent.
	 *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
	 * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterByUpdatedAt($updatedAt = null, $comparison = null)
	{
		if (is_array($updatedAt)) {
			$useMinMax = false;
			if (isset($updatedAt['min'])) {
				$this->addUsingAlias(DBFormPeer::UPDATED_AT, $updatedAt['min'], Criteria::GREATER_EQUAL);
				$useMinMax = true;
			}
			if (isset($updatedAt['max'])) {
				$this->addUsingAlias(DBFormPeer::UPDATED_AT, $updatedAt['max'], Criteria::LESS_EQUAL);
				$useMinMax = true;
			}
			if ($useMinMax) {
				return $this;
			}
			if (null === $comparison) {
				$comparison = Criteria::IN;
			}
		}
		return $this->addUsingAlias(DBFormPeer::UPDATED_AT, $updatedAt, $comparison);
	}

	/**
	 * Filter the query on the id column
	 *
	 * Example usage:
	 * <code>
	 * $query->filterById(1234); // WHERE id = 1234
	 * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
	 * $query->filterById(array('min' => 12)); // WHERE id > 12
	 * </code>
	 *
	 * @param     mixed $id The value to use as filter.
	 *              Use scalar values for equality.
	 *              Use array values for in_array() equivalent.
	 *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
	 * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function filterById($id = null, $comparison = null)
	{
		if (is_array($id) && null === $comparison) {
			$comparison = Criteria::IN;
		}
		return $this->addUsingAlias(DBFormPeer::ID, $id, $comparison);
	}

	/**
	 * Exclude object from result
	 *
	 * @param     DBForm $dBForm Object to remove from the list of results
	 *
	 * @return    DBFormQuery The current query, for fluid interface
	 */
	public function prune($dBForm = null)
	{
		if ($dBForm) {
			$this->addUsingAlias(DBFormPeer::ID, $dBForm->getId(), Criteria::NOT_EQUAL);
		}

		return $this;
	}

	// timestampable behavior
	
	/**
	 * Filter by the latest updated
	 *
	 * @param      int $nbDays Maximum age of the latest update in days
	 *
	 * @return     DBFormQuery The current query, for fluid interface
	 */
	public function recentlyUpdated($nbDays = 7)
	{
		return $this->addUsingAlias(DBFormPeer::UPDATED_AT, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
	}
	
	/**
	 * Filter by the latest created
	 *
	 * @param      int $nbDays Maximum age of in days
	 *
	 * @return     DBFormQuery The current query, for fluid interface
	 */
	public function recentlyCreated($nbDays = 7)
	{
		return $this->addUsingAlias(DBFormPeer::CREATED_AT, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
	}
	
	/**
	 * Order by update date desc
	 *
	 * @return     DBFormQuery The current query, for fluid interface
	 */
	public function lastUpdatedFirst()
	{
		return $this->addDescendingOrderByColumn(DBFormPeer::UPDATED_AT);
	}
	
	/**
	 * Order by update date asc
	 *
	 * @return     DBFormQuery The current query, for fluid interface
	 */
	public function firstUpdatedFirst()
	{
		return $this->addAscendingOrderByColumn(DBFormPeer::UPDATED_AT);
	}
	
	/**
	 * Order by create date desc
	 *
	 * @return     DBFormQuery The current query, for fluid interface
	 */
	public function lastCreatedFirst()
	{
		return $this->addDescendingOrderByColumn(DBFormPeer::CREATED_AT);
	}
	
	/**
	 * Order by create date asc
	 *
	 * @return     DBFormQuery The current query, for fluid interface
	 */
	public function firstCreatedFirst()
	{
		return $this->addAscendingOrderByColumn(DBFormPeer::CREATED_AT);
	}

} // BaseDBFormQuery