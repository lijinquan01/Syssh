<?php
class News_model extends SS_Model{
	function __construct(){
		parent::__construct();
	}

	function fetch($id){
		$query="SELECT * FROM news WHERE id = '".$id."'";
		return db_fetch_first($query,true);
	}
	
	function getList($rows=NULL){
		$q="SELECT * FROM news WHERE display=1 AND company={$this->config->item('company')}";
		
		if(is_null($rows)){
			$q=$this->search($q,array('title'=>'标题'));		    
		}
		$q=$this->orderby($q,'time','DESC');
		if(is_null($rows)){
		    $q=$this->pagination($q);
		}else{
		    $q.=" LIMIT {$rows}";
		}
		
		return $this->db->query($q)->result_array();
	}
}
?>