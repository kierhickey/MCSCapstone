<?php
class School_model extends Model{





	function School_model(){
		parent::Model();
		$this->load->library('gradient');
	}

	function GetInfo(){
		$query_str = "SELECT school_id, name, website, colour, logo, bia, d_columns, displaytype, recurring_price AS recurringPrice, casual_price AS casualPrice, admin_cancel_email FROM school LIMIT 1";
		$query = $this->db->query($query_str);
		if($query->num_rows() ==1){
			return $query->row();
		} else {
			return false;
		}
	}

	public function get($info, $schoolId) {
		if (is_array($info)) {
			$info = implode(", ", $info);
		}

		$queryStr = "SELECT $info FROM school WHERE school_id = $schoolId LIMIT 1";
		$query = $this->db->query($queryStr);

		if ($query != false) {
			return $query->result_array()[0];
		} else {
			return null;
		}
	}

	/**
	* ADD SCHOOL
	*/
	function add($data){
		// Run query to insert blank row
		$this->db->insert('school', array('school_id' => 0) );
		// Get id of inserted record
		$school_id = $this->db->insert_id();
		// Now call the edit function to update the actual data for this new row now we have the ID
		return $this->edit( 'school_id', $school_id, $data );
	}

	/**
	* EDIT SCHOOL
	*/
	function edit($column, $value, $data){
		$this->db->where($column, $value);
		$result = $this->db->update('school', $data);
		// Return bool on success
		if( $result ){
			return true;
		}
		return false;
	}

	function schoolcode_restricted($schoolcode){
		if( in_array( $schoolcode, $this->restricted_codes ) ){
			return true;
		} else {
			return false;
		}
	}

	function GetSchoolName($schoolcode){
		$query_str = "SELECT name FROM schools WHERE code='$schoolcode' LIMIT 1";
		$query = $this->db->query($query_str);
		if($query->num_rows() == 1){
			$row = $query->row();
			return $row->name;
		} else {
			return false;
		}
	}

	function delete_logo($school_id = NULL){
		if($school_id == NULL){ $school_id = $this->session->userdata('school_id'); }
		$row = $this->GetInfo();	//ByCode($schoolcode);
		$logo = $row->logo;
		@unlink('webroot/images/schoollogo/300/'.$logo);
		@unlink('webroot/images/schoollogo/200/'.$logo);
		@unlink('webroot/images/schoollogo/100/'.$logo);
		$this->db->where('school_id', $school_id);
		$this->db->update('school', array('logo' => ''));
	}
}
?>
