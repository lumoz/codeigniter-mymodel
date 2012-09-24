CodeIgniter base MyModel
=================================

This is my base model that extends CI_Model and is extended from other Model.

-----

####How to install

1. Add the file MY_Model.php in /application/core folder.

####How to use

1. Extend your model with:

		class Your_Model extends MY_Model {
			…

2. Define model table name in model:

		protected $_table = 'your_table_name';

3. Use it.

####Public function list:

- `assign` Assign item to class (without loading it)
- `unassign` Clears the ID assignment
- `get_id` Return the current item ID
- `exists` Check if the item exists (with a db query)
- `assign_by` Select item ID from a clause
- `get` Select assigned item data
- `get_by` Select item data from clause
- `gets` Select (all) items from clause
- `get_table` Return table name
- `delete` Delete current assigned item
- `delete_by` Delete item(s) from clause
- `update` Update assigned item
- `update_by` Update item(s) from clause
- `insert` Insert item in DB
- `set_message` Set internal message
- `get_message` Return internal message

###How to work

1. How to select item data:

		$this->yourmodel->assign(1);
		$data = $this->yourmodel->get();

2. How to assign item by email and get data:

		$this->yourmodel->assign_by(array(
			'email'	=> 'user@email.com'
		));
		$data = $this->yourmodel->get();

3. How to update item;

		$this->yourmodel->assign(1);
		$this->yourmodel->update(array(
			'email'	=> 'newuser@email.com'
			, 'status'	=> 'enabled'
		));

4. Ho to insert new item:

		$this->yourmodel->insert(array(
			'username'	=> 'app_user'
			, 'email'		=> 'app_user@email.com'
			, 'status'		=> 'disabled'
			, 'date'		=> date('Y-m-d H:i:s')
		));

5. How to select all items:

		$this->yourmodel->gets();
	
6. How to select all enabled items:

		$this->yourmodel->gets(array(
			'status'	=> 'enabled'
		));

7. How to delete all disabled items:

		$this->yourmodel->delete_by(array(
			'status'	=> 'disabled'
		));

8. Hot to assign after insert callback:

	In Your_Model add this variable:
	
		public $before_insert = array( 'your_method' );
		
		protected function your_method($boot_data) {
			// work with $book_data
			return $book_data;
		}

9. And so on.


Inspired by [codeigniter-base-model](https://github.com/jamierumbelow/codeigniter-base-model) of [Jamie Rumbelow](https://github.com/jamierumbelow).