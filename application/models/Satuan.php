<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

define('GROUP', 'GROUP');
define('DROPDOWN', 'DROPDOWN');
define('DECIMAL', 'DECIMAL');
define('DATE', 'DATE');
define('TEXT', 'TEXT');

const DEFINITION_TYPES = [GROUP, DROPDOWN, DECIMAL, TEXT, DATE];

/**
 * Satuan class
 */

class Satuan extends CI_Model
{
	const SHOW_IN_ITEMS = 1;
	const SHOW_IN_SALES = 2;
	const SHOW_IN_RECEIVINGS = 4;

	public static function get_satuan_flags()
	{
		$class = new ReflectionClass(__CLASS__);

		return array_flip($class->getConstants());
	}

	/*
	 Determines if a given satuan_id is an attribute
	 */
	public function exists($satuan_id, $deleted = FALSE)
	{
		$this->db->from('custom_satuan');
		$this->db->where('satuan_id', $satuan_id);
		$this->db->where('deleted', $deleted);

		return ($this->db->get()->num_rows() == 1);
	}

	

	/*
	 Gets information about a particular attribute satuan
	 */
	public function get_info($satuan_id)
	{
		$this->db->select('satuan.*');
		$this->db->from('custom_satuan AS satuan');
		$this->db->where('satuan.satuan_id', $satuan_id);

		$query = $this->db->get();

		if($query->num_rows() == 1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $item_id is NOT an item
			$item_obj = new stdClass();

			//Get all the fields from items table
			foreach($this->db->list_fields('custom_satuan') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	 Performs a search on attribute satuans
	 */
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'satuan.satuan_name', $order = 'asc')
	{
		$this->db->select('satuan.*');
		$this->db->from('custom_satuan AS satuan');
		

		$this->db->group_start();
		$this->db->like('satuan.satuan_name', $search);
		$this->db->group_end();
		$this->db->where('satuan.deleted', 0);
		$this->db->order_by($sort, $order);

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	public function get_attributes_by_item($item_id)
	{
		$this->db->from('custom_satuan');
		$this->db->join('attribute_links', 'attribute_links.satuan_id = custom_satuan.satuan_id');
		$this->db->where('item_id', $item_id);
		$this->db->where('receiving_id');
		$this->db->where('sale_id');
		$this->db->where('deleted', 0);

		$results = $this->db->get()->result_array();

		return $this->_to_array($results, 'satuan_id');
	}

	public function get_values_by_satuans($satuan_ids)
	{
		if(count($satuan_ids ? : []))
		{
			$this->db->from('custom_satuan');

			$this->db->group_start();
			$this->db->where_in('satuan_id', array_keys($satuan_ids));
			
			$this->db->group_end();

			$this->db->where('deleted', 0);

			$results = $this->db->get()->result_array();

			return $this->_to_array($results, 'satuan_id');
		}

		return array();
	}


	public function get_satuans_by_flags($satuan_flags)
	{
		$this->db->from('custom_satuan');
		$this->db->where('satuan_flags &', $satuan_flags);
		$this->db->where('deleted', 0);
		$this->db->order_by('satuan_id');
		$results = $this->db->get()->result_array();

		return $this->_to_array($results, 'satuan_id', 'satuan_name');
	}


	/**
	 * Returns an array of attribute satuan names and IDs
	 *
	 * @param 	boolean		$groups		If FALSE does not return GROUP type attributes in the array
	 * @return	array					Array containing satuan IDs, attribute names and -1 index with the local language '[SELECT]' line.
	 */
	public function get_satuan_names($groups = TRUE)
	{
		$this->db->from('custom_satuan');
		$this->db->where('deleted', 0);

		if($groups === FALSE)
		{
			$this->db->where_not_in('satuan_type',GROUP);
		}

		$results = $this->db->get()->result_array();

		$satuan_name = array(-1 => $this->lang->line('common_none_selected_text'));

		return $satuan_name + $this->_to_array($results, 'satuan_id', 'satuan_name');
	}

	public function get_satuan_values($satuan_id)
	{
		$attribute_values = [];

		if($satuan_id > -1)
		{
			$this->db->from('attribute_links');
			$this->db->join('attribute_values', 'attribute_values.attribute_id = attribute_links.attribute_id');
			$this->db->where('satuan_id', $satuan_id);
			$this->db->where('item_id');

			$results = $this->db->get()->result_array();

			return $this->_to_array($results, 'attribute_id', 'attribute_value');
		}

		return $attribute_values;
	}

	private function _to_array($results, $key, $value = '')
	{
		return array_column(array_map(function($result) use ($key, $value) {
			return [$result[$key], empty($value) ? $result : $result[$value]];
		}, $results), 1, 0);
	}

	/*
	 Gets total of rows
	 */
	public function get_total_rows()
	{
		$this->db->from('custom_satuan');
		$this->db->where('deleted', 0);

		return $this->db->count_all_results();
	}

	/*
	 Get number of rows
	 */
	public function get_found_rows($search)
	{
		return $this->search($search)->num_rows();
	}

	/*
	 Inserts or updates a satuan
	 */
	public function save_satuan(&$satuan_data, $satuan_id = -1)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		//Definition doesn't exist
		if($satuan_id === -1 || !$this->exists($satuan_id))
		{
			$success = $this->db->insert('custom_satuan', $satuan_data);
			$satuan_data['satuan_id'] = $this->db->insert_id();
		}

		//Definition already exists
		else
		{
			$this->db->select('satuan_name');
			$this->db->from('custom_satuan');
			$this->db->where('satuan_id', $satuan_id);

			$row = $this->db->get()->row();
			
			$from_satuan_name = $row->satuan_name;
			
			$this->db->where('satuan_id', $satuan_id);
			$success = $this->db->update('custom_satuan', $satuan_data);
			$satuan_data['satuan_id'] = $satuan_id;
		}

		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	public function get_satuan_by_name($satuan_name, $satuan_type = FALSE)
	{
		$this->db->from('custom_satuan');
        $this->db->where('satuan_name', $satuan_name);
        
		return $this->db->get()->result_array();
	}

	/**
	 * Deletes an Satuan satuan from the database and associated column in the items_import.csv
	 *
	 * @param	unknown	$satuan_id	Satuan satuan ID to remove.
	 * @return 	boolean					TRUE if successful and FALSE if there is a failure
	 */
	public function delete_satuan($satuan_id)
	{
		$this->db->where('satuan_id', $satuan_id);

		return $this->db->update('custom_satuan', array('deleted' => 1));
	}

	public function delete_satuan_list($satuan_ids)
	{
		$this->db->where_in('satuan_id', $satuan_ids);

		return $this->db->update('custom_satuan', array('deleted' => 1));
	}
}
