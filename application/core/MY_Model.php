<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Base model to extends default CI Model
 * @author	Luigi Mozzillo <luigi@innato.it>
 * @link	http://innato.it
 * @version	1.0.5
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
	protected $primary_key		= 'id';		// Primary id name
	protected $_id				= 0;		// Instantiated element identifier

	protected $before_assign	= array();	// Callback before assigning
	protected $after_assign		= array();	// Callback after assigning
	protected $before_create	= array();	// Callback before the creation
	protected $after_create		= array();	// Callback after the creation
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
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	// --------------------------------------------------------------------------

	/**
	 * Assign item to class (without loading it).
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function assign($id) {
		$this->_run_before_callbacks('assign');
		$this->_id = $id;
		$this->_run_after_callbacks('assign');
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Clears the ID assignment.
	 *
	 * @access public
	 * @return void
	 */
	public function unassign() {
		$this->_id = 0;
		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Return the current item ID.
	 *
	 * @access public
	 * @return void
	 */
	public function get_id() {
		return $this->_id;
	}

	// --------------------------------------------------------------------------

	/**
	 * Verify if items is assigned (without a db query).
	 *
	 * @access public
	 * @return void
	 */
	public function assigned() {
		return $this->get_id() ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------------

	/**
	 * Verify if the item exists (with a db query).
	 *
	 * @access public
	 * @return void
	 */
	public function exists() {
		if (empty($this->_id))
			return FALSE;
		$this->db->select($this->primary_key);
		$res = $this->get();
		return !empty($res) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------------

	/**
	 * Select item ID from a clause.
	 *
	 * @access public
	 * @param mixed $where
	 * @param mixed $escape (default: TRUE)
	 * @return void
	 */
	public function assign_by($where, $escape = TRUE) {
		$this->db->select($this->primary_key);
		$row = $this->get_by($where, $escape = TRUE);
		$this->assign(isset($row->{$this->primary_key}) ? $row->{$this->primary_key} : 0);
		return $this->assigned();
	}

	// --------------------------------------------------------------------------

	/**
	 * Select assigned item data.
	 *
	 * @access public
	 * @return void
	 */
	public function get() {
		return $this->get_by(array(
			$this->primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Select item data from clause.
	 *
	 * @access public
	 * @param mixed $where
	 * @param mixed $escape (default: TRUE)
	 * @return void
	 */
	public function get_by($where, $escape = TRUE) {
		$this->_run_before_callbacks('get');
		$this->db->where($where, NULL, $escape);
		$row = $this->db->get($this->_table)
			->row();
		$this->_run_after_callbacks('get', array($row));
		return $row;
	}

	// --------------------------------------------------------------------------

	/**
	 * Select (all) items from clause.
	 *
	 * @access public
	 * @param array $where (default: array())
	 * @return void
	 */
	public function gets($where = array()) {
		$this->_run_before_callbacks('get');
		$result = $this->db->where($where)
			->get($this->_table)
			->result();
		foreach ($result as &$row)
			$row = $this->_run_after_callbacks('get', array( $row ));
		return $result;
	}

	// --------------------------------------------------------------------------

	/**
	 * Return table name.
	 *
	 * @access public
	 * @return void
	 */
	public function get_table($as = NULL) {
		return $this->_table . ( ! is_null($as) ? ' AS '. $as : '');
	}

	// --------------------------------------------------------------------------

	/**
	 * Execute callback before functions.
	 *
	 * @access private
	 * @param mixed $type
	 * @param array $params (default: array())
	 * @return void
	 */
	private function _run_before_callbacks($type, $params = array()) {
		$name = 'before_' . $type;
		if (!empty($name)) {
			$data = (isset($params[0])) ? $params[0] : array();
			foreach ($this->$name as $method)
				$data = call_user_func_array(array($this, $method), $params);
		}
		return $data;
	}

	// --------------------------------------------------------------------------

	/**
	 * Execute callback after functions.
	 *
	 * @access private
	 * @param mixed $type
	 * @param array $params (default: array())
	 * @return void
	 */
	private function _run_after_callbacks($type, $params = array()) {
		$name = 'after_' . $type;
		if (!empty($name)) {
			$data = (isset($params[0])) ? $params[0] : array();
			foreach ($this->$name as $method)
				$data = call_user_func_array(array($this, $method), $params);
		}
		return $data;
	}

	// --------------------------------------------------------------------------

	/**
	 * Count all results from the table adding eventually a where clause.
	 *
	 * @access public
	 * @param mixed $where (default: NULL)
	 * @return void
	 */
	public function count($where = NULL) {
		if (!is_null($where))
			$this->db->where($where);
		return $this->db->count_all_results($this->_table);
	}

	// --------------------------------------------------------------------------

	/**
	 * Delete current assigned item.
	 *
	 * @access public
	 * @return void
	 */
	public function delete() {
		return $this->delete_by(array(
			$this->primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Delete item(s) from clause.
	 *
	 * @access public
	 * @param mixed $where
	 * @return void
	 */
	public function delete_by($where) {
		$data = $this->_run_before_callbacks('delete', array($where));
		$result = $this->db->where($where)
  			->delete($this->_table);
		$this->_run_after_callbacks('delete', array($where, $result));

		return $result;
	}

	// --------------------------------------------------------------------------

	/**
	 * Update assigned item.
	 *
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function update($data) {
		return $this->update_by($data, array(
			$this->primary_key => $this->_id
		));
	}

	// --------------------------------------------------------------------------

	/**
	 * Update item(s) from clause.
	 *
	 * @access public
	 * @param mixed $data
	 * @param string $where (default: '')
	 * @return void
	 */
	public function update_by($data, $where = array()) {
		$data = $this->_run_before_callbacks('update', array($data));
		$this->db->where($where);
		$result = $this->db->update($this->_table, $data);
		$this->_run_after_callbacks('update', array($data, $result));
		return $result;
	}

	// --------------------------------------------------------------------------

	/**
	 * Insert item in DB.
	 *
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function insert($data) {
		$data = $this->_run_before_callbacks('create', array($data));
		$this->db->insert($this->_table, $data);
		$this->_run_after_callbacks('create', array($data, $this->db->insert_id()));
		return $this->db->insert_id();
	}

	// --------------------------------------------------------------------------

	/**
	 * Return internal message.
	 *
	 * @access public
	 * @return void
	 */
	public function get_message() {
		return $this->message;
	}

	// --------------------------------------------------------------------------

	/**
	 * Set internal message.
	 *
	 * @access public
	 * @param mixed $message
	 */
	public function set_message($message) {
		$this->message = $message;
	}

}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */