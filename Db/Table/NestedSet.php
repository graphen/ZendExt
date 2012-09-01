<?php
/**
 * Klasa implementująca strukturę drzewiastą 
 * dla danych zapisanych w bazie danych.
 *
 * @author Przemysław Szamraj <szamraj@gmail.com>
 * @license FreeBSD
 *  
 */
class ZendExt_Db_Table_NestedSet extends Zend_Db_Table_Abstract {
	
	const LEFT_TABLE_ALIAS = 'children';
	const RIGHT_TABLE_ALIAS = 'parents';

	const FIRST_CHILD = 'first_child';
	const LAST_CHILD = 'last_child';
	const FIRST_SIBLING = 'first_child';
	const LAST_SIBLING = 'last_child';	
	const PREV_SIBLING = 'prev_sibling';
	const NEXT_SIBLING = 'next_sibling';	
	
    /**
     * Nazwa używanej tabeli bazy danych
     *
     * @var string
     */	
	protected $_name = 'categories';
	
    /**
     * Nazwa kolumny z kluczem głównym, identyfikatorem węzła
     *
     * @var string
     */	
	protected $_primary = 'id';
	
    /**
     * Nazwa kolumny z wartościami left
     *
     * @var string
     */	
	protected $_leftColumnName = 'lft';
	
    /**
     * Nazwa kolumny z wartościami right
     *
     * @var string
     */	
	protected $_rightColumnName = 'rgt';
	
    /**
     * Nazwa kolumny z identyfikatorami węzłów rodziców
     *
     * @var string
     */	
	protected $_parentColumnName = 'parent_id';
	
    /**
     * Ustawia domyślne zachowanie dla niektórych metod zwracających węzły, aby zwracały także węzeł root
     *
     * @var bool
     */	
	protected $_rootNodeInResults = true;
	
    /**
     * Nazwa kolumny z identyfikatorem menu, w przypadku wykorzystania klasy do generowania menu
     *
     * @var string
     */	
	protected $_menuColumnName = 'menu_id';
	
    /**
     * Konstruktor
     */		
	public function __construct() {
		$this->_setupFields();
		parent::__construct();
	}
	
	/**
	 * Metoda nadpisująca działanie zdefiniowanej w klasie rodzica. Klucze główne nie mogą być złożone
	 *
	 * @return void
	 */		
	protected function _setupPrimaryKey() {
		parent::_setupPrimaryKey();
		if (count($this->_primary) > 1) {
			throw new Zend_Exception('Obsługiwane są tylko klucze główne założone dla pojedynczej kolumny');
		}
	}
	
	/**
	 * Metoda sprawdzająca niezbędną konfigurację pól 
	 *
	 * @return void
	 */		
	protected function _setupFields() {
		if(empty($this->_name) || empty($this->_leftColumnName) || empty($this->_rightColumnName) || empty($this->_parentColumnName)) {
			throw new Zend_Exception('Nie skonfigurowano wszystkich pól klasy');
		}
		if(empty($this->_menuColumnName)) {
			$this->_menuColumnName = 'menu_id';
		}
		if(empty($this->_rootNodeInResults)) {
			$this->_rootNodeInResults = true;
		}
	}
	
	/**
	 * Ustawia czy węzeł root ma być dołączny do wyników zwracanych przez niektóre metody
	 *
	 * @param bool True, jesli węzeł root ma być w wynikach
	 * @return void
	 */		
	public function setRootNodeInResults($what=true) {
		$this->_rootNodeInResults = (bool)$what;
	}
	
	/**
	 * Zwraca dany węzeł
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */	 		
	public function getNode($nodeId) {
		$select = $this->select();
		$select->from($this->_name, array('*'));
		$select->where($this->_name.'.'.$this->_primary[1].'=?', intval($nodeId));
		$select->limit(1);
		$result = parent::fetchRow($select);		
		if($result !== null) {
			return $result;
		}
		else {
			return null;
		}			
	}
 	
	/**
	 * Zwraca rodzica danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */	 	
	public function getParent($nodeId) {
		$select = $this->select();
		$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array(self::RIGHT_TABLE_ALIAS.'.*'));
		$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array());
		$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_primary[1].'='.self::LEFT_TABLE_ALIAS.'.'.$this->_parentColumnName);
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1].'=?', intval($nodeId));
		$select->limit(1);	
		$result = parent::fetchRow($select);		
		if($result !== null) {
			return $result;
		}
		else {
			return null;
		}		
	}
	
	/**
	 * Zwraca węzeł root
	 *
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getRoot() {
		$select = $this->select();
		$select->from($this->_name, array('*'));
		$select->where($this->_name.'.'.$this->_parentColumnName.' IS NULL');
		$select->limit(1);
		$result = parent::fetchRow($select);		
		if($result !== null) {
			return $result;
		}
		else {
			return null;
		}		
	}
		
	/**
	 * Zwraca określone dziecko danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @param string Rodzaj dziecka (piewrszy/ostatni)
	 * @return Zend_Db_Table_Row|null
	 */			
	public function getChild($nodeId, $type) {
		if($type === self::FIRST_CHILD) {
			$orderOpt = ' ASC';
		}
		if($type === self::LAST_CHILD) {
			$orderOpt = ' DESC';
		}
		$select = $this->select();
		$select->from($this->_name, array('*'));
		$select->where($this->_name.'.'.$this->_parentColumnName.'=?',intval($nodeId));
		$select->order($this->_name.'.'.$this->_rightColumnName.$orderOpt);
		$select->limit(1);
		$result = parent::fetchRow($select);	
		if($result !== null) {
			return $result;
		}
		else {
			return null;
		}
	}
	
	/**
	 * Zwraca pierwsze dzecko danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getFirstChild($nodeId) {
		return $this->getChild($nodeId, self::FIRST_CHILD);
	}
	
	/**
	 * Zwraca ostatnie dziecko danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getLastChild($nodeId) {
		return $this->getChild($nodeId, self::LAST_CHILD);
	}	
	
	/**
	 * Zwraca określonego brata danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @param string Rodzaj brata (pierwszy/ostatni/wcześniejszy/nastepny)
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getSibling($nodeId, $type) {
		$select = $this->select();
		if(($type === self::PREV_SIBLING) || ($type === self::NEXT_SIBLING)) {
			if($type === self::PREV_SIBLING) {
				$operator = '<';
				$orderOpt = ' DESC';
			}
			if($type === self::NEXT_SIBLING) {
				$operator = '>';
				$orderOpt = ' ASC';
			}
			$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array(self::RIGHT_TABLE_ALIAS.'.*'));
			$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array());
			$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1].'=?', intval($nodeId));
			$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_parentColumnName.'='.self::LEFT_TABLE_ALIAS.'.'.$this->_parentColumnName);
			$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.$operator.self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName);
			$select->order(self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.$orderOpt);
			$select->limit(1);
		}	
		if(($type === self::FIRST_SIBLING) || ($type === self::LAST_SIBLING)) {
			if($type === self::FIRST_SIBLING) {
				$operator = '<';
				$orderOpt = ' ASC';
			}
			if($type === self::LAST_SIBLING) {
				$operator = '>';
				$orderOpt = ' DESC';
			}
			$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array(self::RIGHT_TABLE_ALIAS.'.*'));
			$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array());
			$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1].'=?', intval($nodeId));
			$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_parentColumnName.'='.self::LEFT_TABLE_ALIAS.'.'.$this->_parentColumnName);
			$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.$operator.self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName);
			$select->order(self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.$orderOpt);
			$select->limit(1);
		}
		$result = parent::fetchRow($select);	
		if($result !== null) {
			return $result;
		}
		else {
			return null;
		}				
	}
	
	/**
	 * Zwraca poprzedniego brata danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getPrevSibling($nodeId) {
		return $this->getSibling($nodeId, self::PREV_SIBLING);
	}
	
	/**
	 * Zwraca następnego brata danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getNextSibling($nodeId) {
		return $this->getSibling($nodeId, self::NEXT_SIBLING);
	}
	
	/**
	 * Zwraca pierwszego brata danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getFirstSibling($nodeId) {
		return $this->getSibling($nodeId, self::FIRST_SIBLING);
	}
	
	/**
	 * Zwraca ostatniego brata danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return Zend_Db_Table_Row|null
	 */		
	public function getLastSibling($nodeId) {
		return $this->getSibling($nodeId, self::LAST_SIBLING);
	}	
	
	/**
	 * Zwraca dzieci danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @param int|array Identyfikator lub tablica identyfikatorów węzłów, które nie mają zostać zwrócone w wyniku
	 * @param bool True jeśli zwrócone mają zostać tylko wartości kluczy głównych dzieci
	 * @return Zend_Db_Table_Rowset|array|null
	 */		
	public function getChildren($nodeId, $without=null, $onlyIds=false) {
		if($onlyIds === true) {
			$columns = $this->_primary[1];
		}
		else {
			$columns = '*';
		}
		
		$select = $this->select();
		$select->from(array($this->_name), array($columns));
		$select->where($this->_name.'.'.$this->_parentColumnName.'=?',intval($nodeId));
		if($without !== null) {
			if(is_array($without) && count($without) > 0) {
				foreach($without AS $id) {
					$select->where($this->_name.'.'.$this->_primary[1].'!=?',intval($id));
				}
			}
			else {
				$select->where($this->_name.'.'.$this->_primary[1].'!=?',intval($without));
			}
		}
		$select->order($this->_name.'.'.$this->_leftColumnName);
		$resultSet = parent::fetchAll($select);		
		if($resultSet->count() > 0) {
			if($onlyIds === true) {
				$ids = array();
				foreach($resultSet AS $result) {
					$ids[] = $result->{$this->_primary[1]};
				}
				return $ids;				
			}
			else {
				return $resultSet;
			}
		}
		else {
			return null;
		}	
	}
	
	/**
	 * Zwraca wartości kluczy głównych dzieci danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @param int|array Identyfikator lub tablica identyfikatorów węzłów, które nie mają zostać zwrócone w wyniku
	 * @return array|null
	 */	 	
	public function getChildrenIds($nodeId, $without=null) {
		return $this->getChildren($nodeId, $without, true);
	}
	
	/**
	 * Zwraca węzły potomków danego węzła
	 *
	 * @param int Wartość Left węzła rodzica
	 * @param int Wartość Right węzła rodzica 
	 * @param bool True jeśli ma zostać zwrócony węzeł rodzica
	 * @param bool True jeśli zwrócone mają zostać tylko wartości kluczy głównych potomków
	 * @return Zend_Db_Table_Rowset|array|null
	 */		
	public function getDescendantsByLeftRight($left, $right, $withNode=false, $onlyIds=false) {
		if($onlyIds === true) {
			$columns = $this->_primary[1];
		}
		else {
			$columns = '*';
		}
		if($withNode === true) {
			$gtoperator = '>=';
			$ltoperator = '<=';			
		}
		else {
			$gtoperator = '>';
			$ltoperator = '<';
		}
		
		$select = $this->select();
		$select->from($this->_name, array($columns));
		$select->where($this->_name.'.'.$this->_leftColumnName.$gtoperator.'?', intval($left));
		$select->where($this->_name.'.'.$this->_rightColumnName.$ltoperator.'?', intval($right));
		$select->order($this->_name.'.'.$this->_leftColumnName);
		$resultSet = parent::fetchAll($select);
		if($resultSet->count() > 0) {		
			if($onlyIds === true) {
				$ids = array();
				foreach($resultSet AS $result) {
					$ids[] = $result->{$this->_primary[1]};
				}
				return $ids;				
			}
			else {
				return $resultSet;
			}
		}
		else {
			return null;
		}		
	}	
	
	/**
	 * Zwraca potomków danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @param bool True jeśli ma zostać zwrócony węzeł rodzica
	 * @param bool True jeśli zwrócone mają zostać tylko wartości kluczy głównych potomków
	 * @return Zend_Db_Table_Rowset|array|null
	 */		
	public function getDescendants($nodeId, $withNode=false, $onlyIds=false) {
		if($onlyIds === true) {
			$columns = $this->_primary[1];
		}
		else {
			$columns = '*';
		}
		if($withNode === true) {
			$gtoperator = '>=';
			$ltoperator = '<=';			
		}
		else {
			$gtoperator = '>';
			$ltoperator = '<';			
		}
		
		$select = $this->select();
		$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array(self::LEFT_TABLE_ALIAS.'.'.$columns));
		$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array());
		$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_primary[1].'=?',intval($nodeId));
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName.$gtoperator.self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName);
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_rightColumnName.$ltoperator.self::RIGHT_TABLE_ALIAS.'.'.$this->_rightColumnName);
		$select->order(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName);
		$resultSet = parent::fetchAll($select);
		if($resultSet->count() > 0) {		
			if($onlyIds === true) {
				$ids = array();
				foreach($resultSet AS $result) {
					$ids[] = $result->{$this->_primary[1]};
				}
				return $ids;				
			}
			else {
				return $resultSet;
			}
		}
		else {
			return null;
		}
	}	
	
	/**
	 * Zwraca wartości kluczy głównych potomków danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @param bool True jesli ma zostać zwrócony węzeł rodzica
	 * @return array|null
	 */	
	public function getDescendantsIds($nodeId, $withNode=false) {
		return $this->getDescendants($nodeId, $withNode, true);
	}
	
	/**
	 * Zwraca węzły nie posiadające dzieci
	 *
	 * @return Zend_Db_Table_Rowset|null
	 */	
	public function getLeafs() {
		$select = $this->select();
		$select->from($this->_name, array('*'));
		$select->where(new Zend_Db_Expr($this->_name.'.'.$this->_rightColumnName.'-'.$this->_name.'.'.$this->_leftColumnName.'=1'));
		$select->order($this->_name.'.'.$this->_leftColumnName.' ASC');
		$resultSet = parent::fetchAll($select);	
		if($resultSet->count() > 0) {
			return $resultSet;
		}
		else {
			return null;
		}		
	}
	
	/**
	 * Zwraca dane wybranych węzłów
	 *
	 * @param array|null Tablica z danymi dla metody where Zend_Db_Select
	 * @param array|null Tablica z danymi dla metody order Zend_Db_Select
	 * @param int|null Ilość rekordów dla metody limit Zend_Db_Select
	 * @param int|null Offset dla metody limit Zend_Db_Select
	 * @return Zend_Db_Table_Rowset|null
	 */		
	public function fetchAll($where = null, $order = null, $limit = null, $offset = null) {
		$select = $this->select();
		$select->from($this->_name, array('*'));
		if($where !== null) {
			if(is_array($where)) {
				foreach($where AS $value) {
					if(is_array($value)) {
						$select->where($value[0], $value[1]);
					}
					else {
						$select->where($value);
					}
				}
			}
		}
		if($order !== null) {
			if(is_array($order)) {
				foreach($order AS $value) {
					$select->order($value);
				}
			}
			else {
				$select->order($order);
			}
		}
		if ($limit !== null || $offset !== null) {
			$select->limit($limit, $offset);
		}
		$resultSet = parent::fetchAll($select);
		if(count($resultSet) > 0) {
			return $resultSet;
		}
		else {
			return null;
		}
	}
	
	/**
	 * Zwraca dane pojedynczego węzła
	 *
	 * @param array|null Tablica z danymi dla metody where Zend_Db_Select
	 * @param array|null Tablica z danymi dla metody order Zend_Db_Select
	 * @return Zend_Db_Table_Row|null
	 */		
	public function fetchOne($where = null, $order = null) {
		$resultSet = $this->fetchAll($where = null, $order = null, 1);
		if($resultSet !== null) {
			return $resultSet[0];
		}
		else {
			return null;
		}
	}
	
	/**
	 * Zwraca drzewo zawierające wszystkie węzły, włącznie z informacją o glębokości
	 *
	 * @param bool|null True, jeśli węzeł root ma znaleść się w wynikach
	 * @param int|null Maksymalna głębokość drzewa 
	 * @return Zend_Db_Table_Rowset|null
	 */		
	public function getTree($rootNodeInResults=null, $maxDepth=null) {
		if($rootNodeInResults === null) {
			$rootNodeInResults = $this->_rootNodeInResults;
		}
		else {
			$rootNodeInResults = (bool)$rootNodeInResults;
		}
		
		$select = $this->select();
		$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array());
		$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array(self::LEFT_TABLE_ALIAS.'.*'));
		$select->columns('(COUNT('.self::RIGHT_TABLE_ALIAS.'.'.$this->_primary[1].') -1) AS depth');		
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName.' BETWEEN '.self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.' AND '.self::RIGHT_TABLE_ALIAS.'.'.$this->_rightColumnName);
		if($rootNodeInResults === false) {
			$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_parentColumnName.' IS NOT NULL');
		}
		$select->group(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1]);
		if($maxDepth !== null) {
			$select->having('depth<=?',intval($maxDepth));
		}
		$select->order(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName);
		$resultSet = parent::fetchAll($select);
		if($resultSet->count() > 0) {
			return $resultSet;
		}
		else {
			return null;
		}
	}
	
	/**
	 * Zwraca drzewo menu zawierające wszystkie węzły wchodzące w skład danego menu, włącznie z informacją o glębokości
	 *
	 * @param int Identyfikator menu
	 * @param int Identyfikator aktualnie przeglądanego wązła w drzewie menu
	 * @param bool True jeśli ma zostać pobrane zawsze cale menu
	 * @return Zend_Db_Table_Rowset|null
	 */		
	public function getMenuTree($menuId, $nodeId=null, $allTree=true) {
		if($allTree === false) {
			if($nodeId === null) {
				$node = $this->getRoot();
				$nodeId = $node->{$this->_primary[1]};
			}
			$nodesIdsArr = array();
			$path = $this->getPath($nodeId, true);
			if($path !== null) {
				if($path->count() == 0) {
					return null;
				}		
				foreach($path AS $node) {
					$nodeChildrenIds = $this->getChildrenIds($node->{$this->_primary[1]});
					if(is_array($nodeChildrenIds)) {
						$nodesIdsArr = array_merge($nodesIdsArr, $nodeChildrenIds);
					}
				}
				if(count($nodesIdsArr) == 0) {
					return null;
				}
				$nodesIdsArr = array_map('intval', $nodesIdsArr);
			}
		}
		
		$select = $this->select();
		$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array());
		$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array(self::LEFT_TABLE_ALIAS.'.*'));
		$select->columns('(COUNT('.self::RIGHT_TABLE_ALIAS.'.'.$this->_primary[1].') -1) AS depth');		
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName.' BETWEEN '.self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.' AND '.self::RIGHT_TABLE_ALIAS.'.'.$this->_rightColumnName);
		if($allTree === false) {
			$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1].' IN ('.implode(', ',$nodesIdsArr).')');
		}
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_menuColumnName.'=?', $menuId);
		$select->group(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1]);
		$select->order(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName);

		$resultSet = parent::fetchAll($select);
		if($resultSet->count() > 0) {
			return $resultSet;
		}
		else {
			return null;
		}	
		
	}
	
	/**
	 * Zwraca tablicę węzłów zawierajacą wszystkie węzły prowadzące do danego, włącznie z informacją o głębokości
	 *
	 * @param int Identyfikator danego węzła
	 * @param bool|null True, jesli węzeł root ma znalesść się w wynikach
	 * @return Zend_Db_Table_Rowset|null
	 */	
	public function getPath($nodeId, $rootNodeInResults=null) {
		if($rootNodeInResults === null) {
			$rootNodeInResults = $this->_rootNodeInResults;
		}
		else {
			$rootNodeInResults = (bool)$rootNodeInResults;
		}
		
		$select = $this->select();
		$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array(self::RIGHT_TABLE_ALIAS.'.*'));
		$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array());
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName.' BETWEEN '.self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName.' AND '.self::RIGHT_TABLE_ALIAS.'.'.$this->_rightColumnName);		
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_primary[1].'=?',intval($nodeId));
		if($rootNodeInResults === false) {
			$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_parentColumnName.' IS NOT NULL');
		}
		$select->order(self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName);
		$resultSet = parent::fetchAll($select);
		if($resultSet->count() > 0) {
			return $resultSet;
		}
		else {
			return null;
		}
	}
	
	/**
	 * Sprawdza czy obiekt jest węzłem root
	 *
	 * @param int Identyfikator węzła
	 * @return bool
	 */	
	public function isRoot($nodeId) {
		$select = $this->select();
		$select->from($this->_name, array('*'));
		$select->where($this->_name.'.'.$this->_parentColumnName.' IS NULL');
		$select->where($this->_name.'.'.$this->_primary[1].'=?', $nodeId);
		$select->limit(1);
		$result = parent::fetchRow($select);	
		if($result !== null) {
			return true;
		}
		else {
			return false;
		}			
	}
	
	/**
	 * Sprawdza czy obiekt jest węzłem bez dzieci
	 *
	 * @param int Identyfikator węzła
	 * @return bool
	 */
	public function isLeaf($nodeId) {
		$select = $this->select();
		$select->from($this->_name, array('*'));
		$select->where(new Zend_Db_Expr($this->_name.'.'.$this->_rightColumnName.'-'.$this->_name.'.'.$this->_leftColumnName.'=1'));
		$select->where($this->_name.'.'.$this->_primary[1].'=?',$nodeId);
		$select->limit(1);
		$result = parent::fetchRow($select);	
		if($result !== null) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Zwraca ilość dzieci danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return int|null
	 */		
	public function getChildrenCount($nodeId) {
		$select = $this->select();
		$select->from($this->_name, array('COUNT('.$this->_name.'.id) AS nodes_count'));
		$select->where($this->_name.'.'.$this->_parentColumnName.'=?',intval($nodeId));
		$select->limit(1);
		$result = parent::fetchRow($select);	
		if($result !== null) {
			return $result->nodes_count;
		}
		else {
			return null;
		}	
	}
	
	/**
	 * Zwraca ilość potomków danego węzła
	 *
	 * @param int Identyfikator danego węzła
	 * @return int|null
	 */	
	public function getDescendantsCount($nodeId) {
		$select = $this->select();
		$select->from(array(self::LEFT_TABLE_ALIAS=>$this->_name), array('COUNT('.self::LEFT_TABLE_ALIAS.'.id) AS nodes_count'));
		$select->from(array(self::RIGHT_TABLE_ALIAS=>$this->_name), array());
		$select->where(self::RIGHT_TABLE_ALIAS.'.'.$this->_primary[1].'=?',intval($nodeId));
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_leftColumnName.'>'.self::RIGHT_TABLE_ALIAS.'.'.$this->_leftColumnName);
		$select->where(self::LEFT_TABLE_ALIAS.'.'.$this->_rightColumnName.'<'.self::RIGHT_TABLE_ALIAS.'.'.$this->_rightColumnName);
		$select->limit(1);
		$result = parent::fetchRow($select);	
		if($result !== null) {
			return $result->nodes_count;
		}
		else {
			return null;
		}
	}		
	
	/**
	 * Wstawia nowy węzeł root w bazie danych
	 *
	 * @return int|false
	 */
	public function addRoot() {
		if($this->getRoot() === null) {
			try {
				$this->_db->beginTransaction();
				$this->_db->exec('LOCK TABLE ' . $this->_name .' WRITE');			
				
				$dataToInsert = array();
				$dataToInsert[$this->_primary[1]] = null;
				$dataToInsert[$this->_leftColumnName] = 1;
				$dataToInsert[$this->_rightColumnName] = 2;
				$dataToInsert[$this->_parentColumnName] = null;
				
				$this->_db->insert($this->_name, $dataToInsert);
				$id = $this->_db->lastInsertId();
				$this->_db->commit();
				$this->_db->exec('UNLOCK TABLES');
				return $id;
			}
			catch(Zend_Exception $e) {
				$this->_db->rollBack();
				$this->_db->exec('UNLOCK TABLES');
				throw $e;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Wstawia nowy węzeł w bazie danych
	 *
	 * @param int|null Identyfikator węzła rodzica. Null tylko w przypadku wstawiania węzła root
	 * @param string|null Pozycja wstawianego węzła (self::FIRST_CHILD, self::LAST_CHILD)
	 * @param array|null Tablica z opcjonalnymi danymi, które mają zostać wstawione, w polach nie wymienione w klasie
	 * @return int|false
	 */		
	public function add($parentNodeId=null, $position=null, Array $data=null) {
		if($parentNodeId === null) {
			return $this->addRoot();
		}
		try {
			$this->_db->beginTransaction();
			$this->_db->exec('LOCK TABLE ' . $this->_name .' WRITE');
			
			$parentNode = $this->getNode($parentNodeId);
			if($parentNode === null) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;				
			}
			
			$leftColumnValue = $parentNode->{$this->_leftColumnName};
			$rightColumnValue = $parentNode->{$this->_rightColumnName};
			$parentId = $parentNode->{$this->_primary[1]};			
			
			if($position === self::FIRST_CHILD) {
				$what = array($this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+2'));
				$where = array($this->_rightColumnName.'>'.$leftColumnValue);
				$this->_db->update($this->_name, $what, $where);
				$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+2'));
				$where = array($this->_leftColumnName.'>'.$leftColumnValue);
				$this->_db->update($this->_name, $what, $where);
				$newNodeLeftValue = $leftColumnValue + 1;
				$newNodeRightValue = $leftColumnValue + 2;
			}
			else {
				$what = array($this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+2'));
				$where = array($this->_rightColumnName.'>='.$rightColumnValue);
				$this->_db->update($this->_name, $what, $where);
				$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+2'));
				$where = array($this->_leftColumnName.'>='.$rightColumnValue);
				$this->_db->update($this->_name, $what, $where);
				$newNodeLeftValue = $rightColumnValue;
				$newNodeRightValue = $rightColumnValue + 1;
			}
			
			$dataToInsert = array();
			$dataToInsert[$this->_primary[1]] = null;
			$dataToInsert[$this->_leftColumnName] = $newNodeLeftValue;
			$dataToInsert[$this->_rightColumnName] = $newNodeRightValue;
			$dataToInsert[$this->_parentColumnName] = $parentId;
			if(is_array($data) && (count($data) > 0)) {
				foreach($data AS $columnName=>$columnValue) {
					$dataToInsert[$columnName] = $columnValue;
				}
			}
			
			$this->_db->insert($this->_name, $dataToInsert);
			$id = $this->_db->lastInsertId();
			
			$this->_db->commit();
			$this->_db->exec('UNLOCK TABLES');
			return $id;
		}
		catch(Zend_Exception $e) {
			$this->_db->rollBack();
			$this->_db->exec('UNLOCK TABLES');
			throw $e;
		}
	}
	
	/**
	 * Wstawia nowy węzeł w bazie danych
	 *
	 * @param int|null Identyfikator węzła rodzica. Null tylko w przypadku wstawiania węzła root
	 * @param string|null Pozycja wstawianego węzła (self::FIRST_CHILD, self::LAST_CHILD)
	 * @param array|null Tablica z opcjonalnymi danymi, które mają zostać wstawione, w polach nie wymienione w klasie
	 * @return int|false
	 */		
	public function insert($parentNodeId=null, $position=null, Array $data=null) {	
		$this->add($parentNodeId, $position, $data);
	}
	
	/**
	 * Akytualizuje dane pojedynczego węzła
	 *
	 * @param int|null Identyfikator węzła rodzica
	 * @param array Tablica z danymi, które mają zostać zmienione
	 * @return bool
	 */		
	public function edit($nodeId, $data) {
		try {
			if(is_array($data)) {
				if(isset($data[$this->_parentColumnName])) {
					$this->move($nodeId, $data[$this->_parentColumnName]);
				}
			}
				
			$this->_db->beginTransaction();
			$this->_db->exec('LOCK TABLE ' . $this->_name .' WRITE, ' . $this->_name . ' AS ' . self::RIGHT_TABLE_ALIAS . ' WRITE, ' . $this->_name . ' AS ' . self::LEFT_TABLE_ALIAS . ' WRITE');			
			
			$dataToUpdate = array();
			if(is_array($data) && (count($data) > 0)) {
				unset($data[$this->_primary[1]]);
				unset($data[$this->_leftColumnName]);
				unset($data[$this->_rightColumnName]);
				unset($data[$this->_parentColumnName]);				
				foreach($data AS $columnName=>$columnValue) {
					$dataToUpdate[$columnName] = $columnValue;
				}
			
				$where = array($this->_primary[1].'='.$this->_db->quote(intval($nodeId), Zend_Db::INT_TYPE));
				$this->_db->update($this->_name, $dataToUpdate, $where);			
			}			
			
			$this->_db->commit();
			$this->_db->exec('UNLOCK TABLES');
			return true;
		}
		catch(Zend_Exception $e) {
			$this->_db->rollBack();
			$this->_db->exec('UNLOCK TABLES');
			throw $e;
		}
	}
	
	/**
	 * Akytualizuje dane pojedynczego węzła
	 *
	 * @param int|null Identyfikator węzła rodzica
	 * @param array Tablica z danymi, które mają zostać zmienione
	 * @return bool
	 */		
	public function update($nodeId, $data) {
		$this->edit($nodeId, $data);
	}	
	
	/**
	 * Usuwa węzeł z bazy danych
	 *
	 * @param int|null Identyfikator węzła
	 * @return bool
	 */			
	public function delete($nodeId) {
		try {
			$this->_db->beginTransaction();
			$this->_db->exec('LOCK TABLE ' . $this->_name .' WRITE');
			
			$node = $this->getNode($nodeId);
			if($node === null) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;
			}
			
			if($this->isRoot($node->{$this->_primary[1]})) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;
			}
			
			$leftColumnValue = $node->{$this->_leftColumnName};
			$rightColumnValue = $node->{$this->_rightColumnName};
			$range = ($rightColumnValue - $leftColumnValue) + 1;
			
			$where = array($this->_leftColumnName . '>=' . $leftColumnValue, $this->_rightColumnName . '<=' . $rightColumnValue);
			$this->_db->delete($this->_name, $where);
			
			$what = array($this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'-'.$range));
			$where = array($this->_rightColumnName.'>'.$rightColumnValue);
			$this->_db->update($this->_name, $what, $where);
			$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'-'.$range));
			$where = array($this->_leftColumnName.'>'.$leftColumnValue);
			$this->_db->update($this->_name, $what, $where);
			
			$this->_db->commit();
			$this->_db->exec('UNLOCK TABLES');
			return true;		
		}
		catch(Zend_Exception $e) {
			$this->_db->rollBack();
			$this->_db->exec('UNLOCK TABLES');
			throw $e;
		}			
	}
	
	/**
	 * Przenosi węzeł wraz z wszystkimi potomkami w inne miejsce w drzewie
	 *
	 * @param int Identyfikator przenoszonego węzła
	 * @param int Identyfikator węzła docelowego
	 * @return bool
	 */	
	public function move($sourceNodeId, $destinationNodeId) {
		try {
			
			$this->_db->beginTransaction();
			$this->_db->exec('LOCK TABLE ' . $this->_name .' WRITE, ' . $this->_name . ' AS ' . self::RIGHT_TABLE_ALIAS . ' WRITE, ' . $this->_name . ' AS ' . self::LEFT_TABLE_ALIAS . ' WRITE');
			
			$sourceNode = $this->getNode($sourceNodeId);
			$destinationNode = $this->getNode($destinationNodeId);
			
			if(($sourceNode === null) || ($destinationNode === null)) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;				
			}
			if($destinationNode->{$this->_primary[1]} === $sourceNode->{$this->_parentColumnName}) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;
			}			
			if($this->isRoot($sourceNode->{$this->_primary[1]}) === true) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;				
			}

			
			$sourceNodeDescendantsIds = $this->getDescendantsIds($sourceNodeId, true);
			
			if(in_array($destinationNode->{$this->_primary[1]}, $sourceNodeDescendantsIds)) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;
			}
			
			$range = ($sourceNode->{$this->_rightColumnName} - $sourceNode->{$this->_leftColumnName}) + 1;
			
			$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'-'.$range));
			$where = array($this->_leftColumnName.'>'.$sourceNode->{$this->_rightColumnName},
						   $this->_primary[1].' NOT IN ('.implode(',',$sourceNodeDescendantsIds).')'
			);
			$this->_db->update($this->_name, $what, $where);			
			$what = array($this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'-'.$range));
			$where = array($this->_rightColumnName.'>'.$sourceNode->{$this->_rightColumnName},
						   $this->_primary[1].' NOT IN ('.implode(', ',$sourceNodeDescendantsIds).')'
			);
			$this->_db->update($this->_name, $what, $where);
			
			
			if($destinationNode->{$this->_leftColumnName} > $sourceNode->{$this->_leftColumnName}) {
				$destinationNode->{$this->_leftColumnName} -= $range;
			}
			if($destinationNode->{$this->_rightColumnName} > $sourceNode->{$this->_rightColumnName}) {
				$destinationNode->{$this->_rightColumnName} -= $range;
			}
			
			$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+'.$range));
			$where = array($this->_leftColumnName.'>'.$destinationNode->{$this->_rightColumnName},
						   $this->_primary[1].' NOT IN ('.implode(',',$sourceNodeDescendantsIds).')'
			);
			$this->_db->update($this->_name, $what, $where);
			$what = array($this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+'.$range));
			$where = array($this->_rightColumnName.'>='.$destinationNode->{$this->_rightColumnName},
						   $this->_primary[1].' NOT IN ('.implode(',',$sourceNodeDescendantsIds).')'
			);
			$this->_db->update($this->_name, $what, $where);
						
			
			$destinationNode->{$this->_rightColumnName} += $range;
			
			$offset = $destinationNode->{$this->_rightColumnName} - ($sourceNode->{$this->_leftColumnName} + $range);
			
			$what = array($this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+'.$offset),
						  $this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+'.$offset)
			);
			$where = array($this->_primary[1] . ' IN ('.implode(',',$sourceNodeDescendantsIds).')');
			$this->_db->update($this->_name, $what, $where);
			
			$what = array($this->_parentColumnName=>$destinationNodeId);
			$where = array($this->_primary[1].'='.$this->_db->quote(intval($sourceNodeId), Zend_Db::INT_TYPE));
			$this->_db->update($this->_name, $what, $where);
			
			$this->_db->commit();
			$this->_db->exec('UNLOCK TABLES');
			return true;		
		}
		catch(Zend_Exception $e) {
			$this->_db->rollBack();
			$this->_db->exec('UNLOCK TABLES');
			throw $e;
		}		
	}
	
	/**
	 * Przesuwa węzeł wraz z wszystkimi potomkami w  o jedno miejsce do przodu lub do tyłu w obrębie tego samego węzła rodzica
	 *
	 * @param int Identyfikator przesuwanego węzła
	 * @param string Sposób przesunięcia (self::PREV_SIBLING lub self::NEXT_SIBLING)
	 * @return bool
	 */	
	public function reorder($nodeId, $destination) {
		try {
			$this->_db->beginTransaction();
			$this->_db->exec('LOCK TABLE ' . $this->_name .' WRITE, ' . $this->_name . ' AS ' . self::RIGHT_TABLE_ALIAS . ' WRITE, ' . $this->_name . ' AS ' . self::LEFT_TABLE_ALIAS . ' WRITE');			
			
			$node = $this->getNode($nodeId);
			if($node == null) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;				
			}
			if($this->isRoot($node->{$this->_primary[1]}) === true) {
				$this->_db->exec('UNLOCK TABLES');
				$this->_db->rollBack();
				return false;				
			}
			
			switch($destination) {
				case self::PREV_SIBLING:
					$prevSibling = $this->getSibling($nodeId, self::PREV_SIBLING);
					if($prevSibling === null) {
						$this->_db->exec('UNLOCK TABLES');
						$this->_db->rollBack();
						return false;								
					}
					
					$nodeDescendantsIds = $this->getDescendantsIds($node->{$this->_primary[1]}, true);
					$prevSiblingDescendantsIds = $this->getDescendantsIds($prevSibling->{$this->_primary[1]}, true);
					
					$offsetForNode = $node->{$this->_leftColumnName} - $prevSibling->{$this->_leftColumnName};
					$offsetForLeftSibling = $node->{$this->_rightColumnName} - $node->{$this->_leftColumnName} + 1;
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'-'.$offsetForNode),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'-'.$offsetForNode)
					);
					$where = array($this->_primary[1].' IN ('.implode(',',$nodeDescendantsIds).')');
					$this->_db->update($this->_name, $what, $where);			
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+'.$offsetForLeftSibling),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+'.$offsetForLeftSibling)
					);
					$where = array($this->_primary[1].' IN ('.implode(',',$prevSiblingDescendantsIds).')');
					$this->_db->update($this->_name, $what, $where);			
					
					break;
				case self::NEXT_SIBLING:
					$nextSibling = $this->getSibling($nodeId, self::NEXT_SIBLING);
					if($nextSibling === null) {
						$this->_db->exec('UNLOCK TABLES');
						$this->_db->rollBack();
						return false;				
					}
							
					$nodeDescendantsIds = $this->getDescendantsIds($node->{$this->_primary[1]}, true);
					$nextSiblingDescendantsIds = $this->getDescendantsIds($nextSibling->{$this->_primary[1]}, true);
					
					$offsetForNode = $nextSibling->{$this->_rightColumnName} - $nextSibling->{$this->_leftColumnName} + 1;
					$offsetForRightSibling = $nextSibling->{$this->_leftColumnName} - $node->{$this->_leftColumnName};	
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+'.$offsetForNode),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+'.$offsetForNode)
					);
					$where = array($this->_primary[1].' IN ('.implode(',',$nodeDescendantsIds).')');
					$this->_db->update($this->_name, $what, $where);			
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'-'.$offsetForRightSibling),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'-'.$offsetForRightSibling)
					);
					$where = array($this->_primary[1].' IN ('.implode(',',$nextSiblingDescendantsIds).')');
					$this->_db->update($this->_name, $what, $where);
							
					break;
				case self::FIRST_SIBLING:
					$firstSibling = $this->getSibling($nodeId, self::FIRST_SIBLING);
					if($firstSibling === null) {
						$this->_db->exec('UNLOCK TABLES');
						$this->_db->rollBack();
						return false;								
					}
					
					$nodeDescendantsIds = $this->getDescendantsIds($node->{$this->_primary[1]}, true);
										
					$offsetForNode = $node->{$this->_leftColumnName} - $firstSibling->{$this->_leftColumnName};
					$offsetForOtherSibling = $node->{$this->_rightColumnName} - $node->{$this->_leftColumnName} + 1;
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'-'.$offsetForNode),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'-'.$offsetForNode)
					);
					$where = array($this->_primary[1].' IN ('.implode(',',$nodeDescendantsIds).')');
					$this->_db->update($this->_name, $what, $where);			
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+'.$offsetForOtherSibling),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+'.$offsetForOtherSibling)
					);
					$where = array($this->_primary[1].' NOT IN ('.implode(',',$nodeDescendantsIds).')',
								   $this->_leftColumnName.'<'.$node->{$this->_leftColumnName},
								   $this->_rightColumnName.'<'.$node->{$this->_leftColumnName},
								   $this->_leftColumnName.'>='.$firstSibling->{$this->_leftColumnName},
								   $this->_rightColumnName.'>='.$firstSibling->{$this->_leftColumnName}							   
					);
					$this->_db->update($this->_name, $what, $where);			
					
					break;					
				case self::LAST_SIBLING:
					$lastSibling = $this->getSibling($nodeId, self::LAST_SIBLING);
					if($lastSibling === null) {
						$this->_db->exec('UNLOCK TABLES');
						$this->_db->rollBack();
						return false;								
					}
						
					$nodeDescendantsIds = $this->getDescendantsIds($node->{$this->_primary[1]}, true);
					
					$offsetForNodeSibling = $lastSibling->{$this->_rightColumnName} - ($node->{$this->_leftColumnName} + ($node->{$this->_rightColumnName} - $node->{$this->_leftColumnName}));					
					$offsetForOtherSibling = $node->{$this->_rightColumnName} - $node->{$this->_leftColumnName} + 1;
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'+'.$offsetForNodeSibling),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'+'.$offsetForNodeSibling)
					);
					$where = array($this->_primary[1].' IN ('.implode(',',$nodeDescendantsIds).')');
					$this->_db->update($this->_name, $what, $where);		 	
					
					$what = array($this->_leftColumnName=>new Zend_Db_Expr($this->_leftColumnName.'-'.$offsetForOtherSibling),
								  $this->_rightColumnName=>new Zend_Db_Expr($this->_rightColumnName.'-'.$offsetForOtherSibling)
					);
					$where = array($this->_primary[1].' NOT IN ('.implode(',',$nodeDescendantsIds).')',
								   $this->_leftColumnName.'<='.$lastSibling->{$this->_rightColumnName},
								   $this->_rightColumnName.'<='.$lastSibling->{$this->_rightColumnName},
								   $this->_leftColumnName.'>'.$node->{$this->_rightColumnName},
								   $this->_rightColumnName.'>'.$node->{$this->_rightColumnName}			
					);
					$this->_db->update($this->_name, $what, $where);
					
					break;				
			}
		
			$this->_db->commit();
			$this->_db->exec('UNLOCK TABLES');
			return true;		
		}
		catch(Zend_Exception $e) {
			$this->_db->rollBack();
			$this->_db->exec('UNLOCK TABLES');
			throw $e;
		}			
	}
	
}

