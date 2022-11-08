<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("Secure_Controller.php");

class Satuans extends Secure_Controller
{
	public function __construct()
	{
		parent::__construct('satuans');
	}

	public function index()
	{
		$data['table_headers'] = $this->xss_clean(get_satuan_definition_manage_table_headers());

		$this->load->view('satuans/manage', $data);
	}

	/*
	Returns customer table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = $this->input->get('search');
		$limit  = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort   = $this->input->get('sort');
		$order  = $this->input->get('order');

		$satuans = $this->Satuan->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Satuan->get_found_rows($search);

		$data_rows = array();
		foreach($satuans->result() as $attribute)
		{
			
			$data_rows[] = get_attribute_definition_data_row($attribute, $this);
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	public function save_attribute_value($attribute_value)
	{
 		$success = $this->Satuan->save_value(urldecode($attribute_value), $this->input->post('satuan_id'), $this->input->post('item_id'), $this->input->post('attribute_id'));

		echo json_encode(array('success' => $success != 0));
	}

	public function delete_attribute_value($attribute_value)
	{
		$success = $this->Satuan->delete_value($attribute_value, $this->input->post('satuan_id'));

		echo json_encode(array('success' => $success));
	}

	public function save_definition($definition_id = -1)
	{

		

		//Save definition data
		$definition_data = array(
			'satuan_name' => $this->input->post('satuan_name')
		);

		$definition_name = $this->xss_clean($definition_data['satuan_name']);

		if($this->Satuan->save_definition($definition_data, $definition_id))
		{
			//New definition
			if($definition_id == -1)
			{
				$definition_values = json_decode($this->input->post('satuan_values'));

				foreach($definition_values as $definition_value)
				{
					$this->Satuan->save_value($definition_value, $definition_data['satuan_id']);
				}

				echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('satuans_definition_successful_adding').' '.
                    $definition_name, 'id' => $definition_data['satuan_id']));
			}
			else //Existing definition
			{
				echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('satuans_definition_successful_updating').' '.
                    $definition_name, 'id' => $definition_id));
			}
		}
		else//failure
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('satuans_definition_error_adding_updating', $definition_name), 'id' => -1));
		}
	}

	public function suggest_attribute($definition_id)
	{
		$suggestions = $this->xss_clean($this->Satuan->get_suggestions($definition_id, $this->input->get('term')));

		echo json_encode($suggestions);
	}

	public function get_row($row_id)
	{
		$attribute_definition_info = $this->Satuan->get_info($row_id);
		
		$data_row = $this->xss_clean(get_attribute_definition_data_row($attribute_definition_info));

		echo json_encode($data_row);
	}

	
	public function view($definition_id = -1)
	{
		$info = $this->Satuan->get_info($definition_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}

		$data['satuan_id'] = $definition_id;
		
		$data['satuan_info'] = $info;

		$show_all = Satuan::SHOW_IN_ITEMS | Satuan::SHOW_IN_RECEIVINGS | Satuan::SHOW_IN_SALES;
		
		$this->load->view("satuans/form", $data);
	}

	public function delete_value($attribute_id)
	{
		return $this->Satuan->delete_value($attribute_id);
	}

	public function delete()
	{
		$satuans_to_delete = $this->input->post('ids');

		if($this->Satuan->delete_definition_list($satuans_to_delete))
		{
			$message = $this->lang->line('satuans_definition_successful_deleted') . ' ' . count($satuans_to_delete) . ' ' . $this->lang->line('satuans_definition_one_or_multiple');
			echo json_encode(array('success' => TRUE, 'message' => $message));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('satuans_definition_cannot_be_deleted')));
		}
	}

}
