<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Base model to extends default CI Model
 * @author	Luigi Mozzillo <luigi@innato.it>
 * @link	http://innato.it
 * @version	1.6
 * @extends CI_Model
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT
 * HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR
 * FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE
 * OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,
 * COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.COPYRIGHT HOLDERS WILL NOT
 * BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL
 * DAMAGES ARISING OUT OF ANY USE OF THE SOFTWARE OR DOCUMENTATION.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://gnu.org/licenses/>.
 */
class MY_Model extends CI_Model {

	protected $_table			= NULL;		// DB Table name
	protected $_table_alias		= NULL;		// DB Table name alias
	protected $_primary_key		= 'id';		// Primary id name
	protected $_id				= 0;		// Instantiated element identifier

	protected $before_assign	= array();	// Callback before assigning
	protected $after_assign		= array();	// Callback after assigning
	protected $before_insert	= array();	// Callback before the creation
	protected $after_insert		= array();	// Callback after the creation
	protected $before_update	= array();	// Callback before updating
	protected $after_update		= array();	// Callback after updating
	protected $before_get		= array();	// Callback before selecting
	protected $after_get		= array();	// Callback after selecting
	protected $before_delete	= array();	// Callback before deleting
	protected $after_delete		= array();	// Callback after deleting

	protected $message			= '';		// Message (error or success)

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		parent::__construct();
	}

	// --------------------------------------------------------------------------

	/**
	 * Assign item to class (without loading it).
	 *
	 * @param  mixed $id
	 * @return object
	 */
	public function assign($id) {
		$this->_run_callbacks('before', 'assign');
		$this->_id = $id;
		$this->_run_callbacks('after', 'assign');
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Clears the ID assignment.
	 *
	 * @return object
	 */
	public function unassign() {
		$this->_id = 0;
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Return the current item ID.
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->_id;
	}

	// --------------------------------------------------------------------------

	/**
	 * Verify if items is assigned (without a db query).
	 *
	 * @return boolean
	 */
	public function assigned() {
		return $this->get_id() ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------------

	/**
	 * Verify if the item exists (with a db query).
	 *
	 * @return boolean
	 */
	public function exists() {
		if (empty($this->_id))
			return FALSE;
		$this->db->select($this->_primary_key);
		$res = $this->get();
		return ! empty($res);
	}

	// --------------------------------------------------------------------------

	/**
	 * Select item ID from a clause.
	 *
	 * @param  array  $where
	 * @param  boolean $escape
	 * @return boolean
	 */
	public function assign_by($where, $escape = TRUE) {
		$this->db->select($this->_primary_key);
		$row = $this->get_by($where, $escape = TRUE);
		$this->assign(
			isset($row->{$this->_primary_key})
				? $row->{$this->_primary_key}
				: 0
		);
		return $this->assigned();
	}

	// --------------------------------------------------------------------------

	/**
	 *  Select assigned item data.
	 *
	 * @return object
	 */
	public function get() {
		return $this->get_by(array(
			$this->_primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Select item data from clause.
	 *
	 * @param  array  $where
	 * @param  boolean $escape
	 * @return object
	 */
	public function get_by($where, $escape = TRUE) {
		$this->_run_callbacks('before', 'get');
		$row = $this->db
			->where($where, NULL, $escape)
			->get($this->get_table())
			->row();
		$this->_run_callbacks('after', 'get', array($row));
		return $row;
	}

	// --------------------------------------------------------------------------

	/**
	 * Select (all) items from clause.
	 *
	 * @param  array  $where
	 * @return array
	 */
	public function gets($where = array()) {
		$this->_run_callbacks('before', 'get');
		$result = $this->db
			->where($where)
			->get($this->get_table())
			->result();
		foreach ($result as & $row) {
			$row = $this->_run_callbacks('after', 'get', array($row));
		}
		return $result;
	}

	// --------------------------------------------------------------------------

	/**
	 * Return table name.
	 * Set alias if you want; use table alias if exists.
	 *
	 * @param  string $as
	 * @return string
	 */
	public function get_table($as = NULL) {
		if ( ! is_null($as)) {
			$this->_table_alias = $as;
		} elseif (is_null($as) && ! is_null($this->_table_alias)) {
			$as = $this->_table_alias;
		}
		return $this->_table . ( ! is_null($as) ? ' AS '. $as : '');
	}

	// ------------------------------------------------------------------------

	/**
	 * Return primary key table field.
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return $this->_primary_key;
	}

	// --------------------------------------------------------------------------

	/**
	 * Set table alias.
	 *
	 * @param  string $as
	 * @return object
	 */
	public function set_alias($as) {
		$this->_table_alias = $as;
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get table alias.
	 *
	 * @return string
	 */
	public function get_alias() {
		return $this->_table_alias;
	}

	// --------------------------------------------------------------------------

	/**
	 * Execute callback before/after functions.
	 *
	 * @param  string $moment
	 * @param  string $operation
	 * @param  array  $parameters
	 * @return array
	 */
	private function _run_callbacks($moment, $operation, $parameters = array()) {

		// Take data
		$data = isset($parameters[0]) ? $parameters[0] : array();

		// Check if exists callback array
		$callback = $moment .'_'. $operation;
		if ( ! empty($this->$callback)) {
			foreach ($this->$callback as $method) {

				// Check if method exists
				if (method_exists($this, $method)) {

					// Execute method
					$data = call_user_func_array(array($this, $method), $parameters);

					// If return FALSE, exit
					if ($data === FALSE)
						return FALSE;

					// Use new $data in next callback
					$parameters = array($data);
				}
			}
		}
		return $data;
	}

	// --------------------------------------------------------------------------

	/**
	 * Count all results from the table adding eventually a where clause.
	 *
	 * @param  array $where
	 * @return integer
	 */
	public function count($where = NULL) {
		if ( ! is_null($where)) {
			$this->db->where($where);
		}
		return $this->db->count_all_results($this->get_table());
	}

	// --------------------------------------------------------------------------

	/**
	 * Delete current assigned item.
	 *
	 * @return object
	 */
	public function delete() {
		return $this->delete_by(array(
			$this->_primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Delete item(s) from clause.
	 *
	 * @param  array $where
	 * @return object
	 */
	public function delete_by($where) {
		$data = $this->_run_callbacks('before', 'delete', array($where));
		$result = $this->db
			->where($where)
  			->delete($this->get_table());
		$this->_run_callbacks('after', 'delete', array($where, $result));
		return $result;
	}

	// --------------------------------------------------------------------------

	/**
	 * Update assigned item.
	 *
	 * @param  array $data
	 * @return object
	 */
	public function update($data) {
		return $this->update_by($data, array(
			$this->_primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Update item(s) from clause.
	 *
	 * @param  array $data
	 * @param  array  $where
	 * @return object
	 */
	public function update_by($data, $where = array()) {

		// Return FALSE if data is empty
		if (empty($data))
			return FALSE;

		// Execute callback before update
		$data = $this->_run_callbacks('before', 'update', array($data));

			// If callback return FALSE, not save
		if ($data === FALSE)
			return FALSE;

		// Update data
		$result = $this->db
			->where($where)
			->update($this->get_table(), $data);

		// Execute callback after update
		$this->_run_callbacks('after', 'update', array($data, $result));

		// Return result
		return $result;
	}

	// --------------------------------------------------------------------------

	/**
	 * Insert item in DB.
	 *
	 * @param  array $data
	 * @return integer
	 */
	public function insert($data) {

		// Return FALSE if data is empty
		if (empty($data))
			return FALSE;

		// Execute callback before insert
		$data = $this->_run_callbacks('before', 'insert', array($data));

		// If callback return FALSE, not save
		if ($data === FALSE)
			return FALSE;

		// Insert data
		$this->db->insert($this->get_table(), $data);

		// Execute callback after insert
		$this->_run_callbacks('after', 'insert', array($data, $this->db->insert_id()));

		// Return last insert id
		return $this->db->insert_id();
	}

	// --------------------------------------------------------------------------

	/**
	 * Increase field value for assigned item.
	 *
	 * @param  string $field
	 * @return object
	 */
	public function increase($field) {
		return $this->increase_by($field, array(
			$this->_primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Increase field value form a clause.
	 *
	 * @param  string $field
	 * @param  array  $where
	 * @return object
	 */
	public function increase_by($field, $where = array()) {
		return $this->db
			->set($field, $field .' + 1', FALSE)
			->where($where)
			->update($this->get_table());
	}

	// --------------------------------------------------------------------------

	/**
	 * Check if a $value is unique in a $field.
	 * If an item is assigned, exclude it.
	 *
	 * @param  string $field
	 * @param  mixed $value
	 * @return integer
	 */
	public function unique($field, $value) {
		if ($this->assigned()) {
			$this->db->where($this->_primary_key  .' != ', $this->_id);
		}
		return ! $this->count(array(
			$field => $value
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Generate and return a random and unique string.
	 *
	 * @param  mixed $field
	 * @param  string $type (default: 'alnum')
	 * @param  int $length (default: 8)
	 * @return string
	 */
	public function random_unique($field, $type = 'alnum', $length = 8) {
		do {
			$unique = random_string($type, $length);
		} while( ! $this->unique($field, $unique));
		return $unique;
	}

	// --------------------------------------------------------------------------

	/**
	 * Create a join from this to other model.
	 *
	 * @param  string $model
	 * @param  string $con
	 * @param  string $as
	 * @param  string $type
	 * @return object
	 */
	public function set_relation($model, $con, $as = NULL, $type = 'left') {

		// Create model name
		$model_name = url_title($model, '-', TRUE);

		// Load model
		$this->load->model($model, $model_name);

		// DB Join
		$this->db->join(
			$this->$model_name->get_table($as)
			, $con
			, $type
		);
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * Add SQL_CALC_FOUND_ROWS to query (to perform pagination).
	 *
	 * @param integer $per_page
	 * @param integer $page
	 * @param string  $select
	 */
	public function set_pagination($per_page, $page = 1, $select = '*') {

		// Add calc_found_rows to query
		$select = 'SQL_CALC_FOUND_ROWS '. $select;
		array_unshift($this->db->ar_select, $select);
		$key = array_search($select, $this->db->ar_select);
		$this->db->ar_no_escape[$key] = FALSE;

		// Add limit query
		$this->db->limit($per_page, $per_page * ($page > 0 ? $page - 1 : 0));
		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * Return rows founded after SQL_CALC_FOUND_ROWS (to perform pagination).
	 *
	 * @return integer
	 */
	public function found_rows() {
		return $this->db
			->query('SELECT FOUND_ROWS() as rows')
			->row()
			->rows;
	}

	// --------------------------------------------------------------------------

	/**
	 * Return internal message.
	 *
	 * @return string
	 */
	public function get_message() {
		return $this->message;
	}

	// --------------------------------------------------------------------------

	/**
	 * Set internal message.
	 *
	 * @param  string $message
	 * @return boolean
	 */
	public function set_message($message) {
		$this->message = $message;
		return FALSE;
	}

}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */