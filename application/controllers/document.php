<?php
class Document extends SS_controller{
	function __construct(){
		parent::__construct();
	}
	
	function office_document(){
		$this->lists(869);
	}
	
	function instrument(){
		$this->lists(870);
	}
	
	function contact_file(){
		$this->lists(872);
	}
	
	function rules(){
		$this->lists(874);
	}
	
	function contract(){
		$this->lists(874);
	}

	function lists($folder_id=NULL){
		if(isset($folder_id)){
			option('in_search_mod',false);

			$folder=$this->document->fetch($folder_id);

			if($folder['type']!=''){
				$this->action="document_download";
				$this->load->require_head=false;
			}else{
				$_SESSION[$class]['upID']=$folder['parent'];
				$_SESSION[$class]['currentDir']=$folder['name'];
				$_SESSION[$class]['currentDirID']=$folder['id'];
				$_SESSION[$class]['currentPath']=$folder['path'];
			}
		}
		
		if(!sessioned('currentPath',NULL,false))
			$_SESSION['document']['currentPath']=$this->config->item('document_root');
		
		if(!sessioned('currentDir',NULL,false))
			$_SESSION['document']['currentDir']='root';
			
		if(!sessioned('currentDirID',NULL,false))
			$_SESSION['document']['currentDirID']=1;
			
		if(!sessioned('upID',NULL,false))
			$_SESSION['document']['upID']='';
		$field=option('in_search_mod')?
			array(
				'checkbox'=>array('title'=>'','content'=>'<input type="checkbox" name="document[{id}]" >','td_title'=>'width=38px'),
				'type'=>array(
					'title'=>'类型',
					'eval'=>true,
					'content'=>"
						if('{type}'==''){
							\$image='folder';
						}elseif(is_file('web/images/file_type/{type}.png')){
							\$image='{type}';
						}else{
							\$image='unknown';
						}
						return '<img src=\"images/file_type/'.\$image.'.png\" alt=\"{type}\" />';
					",
					'td_title'=>'width="70px"'
				),
				'name'=>array('title'=>'文件名','td_title'=>'width="150px"','wrap'=>array('mark'=>'a','href'=>'/document?view={id}')),
				'path'=>array('title'=>'路径'),'comment'=>array('title'=>'备注')
			)
			:
			array(
				'checkbox'=>array('title'=>'','content'=>'<input type="checkbox" name="document[{id}]" >','td_title'=>' width=38px'),
				'type'=>array(
					'title'=>'类型',
					'eval'=>true,
					'content'=>"
						if('{type}'==''){
							\$image='folder';
						}elseif(is_file('images/file_type/{type}.png')){
							\$image='{type}';
						}else{
							\$image='unknown';
						}
						return '<img src=\"/images/file_type/'.\$image.'.png\" alt=\"{type}\" />';
					",
					'td_title'=>'width="55px"'
				),
				'name'=>array('title'=>'文件名','td_title'=>'width="150px"','wrap'=>array('mark'=>'a','href'=>'/document?view={id}')),
				'username'=>array('title'=>'上传者','td_title'=>'width="70px"'),
				'comment'=>array('title'=>'备注')
			);
		$table=$this->table->setFields($field)
			->setMenu('<input type="submit" name="fav" value="收藏" />'.
					($_SESSION['document']['currentDirID']>1?"<button type='button' name='view' value='0' onclick='redirectPara(this)'>顶级</button><button type='button' name='view' value='".$_SESSION['document']['upID']."' onclick='redirectPara(this)'>上级</button>":'').
					(option('in_search_mod')?'':$_SESSION['document']['currentPath']),'left')
			->setData($this->document->getList())
			->generate();
		$this->load->addViewData('list',$table);
		$this->load->view('list');
	}

	function createDir(){
		$dirPath=iconv("utf-8","gbk",$_SESSION['document']['currentPath']."/".$this->input->post('dirName'));
		mkdir($dirPath);
		$dir=array(
			'name'=>$this->input->post('dirName'),
			'parent'=>$_SESSION['document']['currentDir'],
			'level'=>$_SESSION['document']['currentLevel'],
			'path'=>$_SESSION['document']['currentPath']."/".$this->input->post('dirName'),
			'parent'=>$_SESSION['document']['currentDirID'],
			'type'=>''
		);
		db_insert('document',$dir);
		
		redirect('document');
	}

	function download(){
		$file=db_fetch_first("SELECT * FROM document WHERE id = '".intval($this->input->get('view'))."'");
		
		document_exportHead($file['name']);
		
		$path=$file['path'];
		$path=iconv("utf-8","gbk",$path);
		readfile($path);
		exit;
	}
	
	function favDelete(){
		$_POST=array_trim($_POST);
		unset($this->input->post('favDelete'));
		print_r($_POST);
		if(isset($_POST)){
			$condition = db_implode($_POST, $glue = ' OR ','file','=',"'","'", '`','key');
			$q="DELETE FROM document_fav WHERE (".$condition.") AND uid='".$_SESSION['id']."'";
			db_query($q);
		}
		redirect('document');
	}
	
	function fav(){
		$_POST=array_trim($_POST);
		if(isset($_POST)){
			$glue=$values='';
			foreach($this->input->post('document') as $id=>$status){
				$values.=$glue."('".$id."','".$_SESSION['id']."','".time()."')";
				$glue=','."\n";
			}
			$q="REPLACE INTO document_fav (file,uid,time) values ".$values;
			db_query($q);
		}
		redirect('document');
	}
	
	function upload(){
		if ($_FILES["file"]["error"] > 0){
			echo "error code: " . $_FILES["file"]["error"] . "<br />";
		}
		else{
			$storePath=iconv("utf-8","gbk",$_SESSION['document']['currentPath']."/".$_FILES["file"]["name"]);//存储路径转码
			
			if (is_file($storePath)){
				unlink($storePath);
				$db_replace=true;
			}else{
				$db_replace=false;
			}
			
			move_uploaded_file($_FILES["file"]["tmp_name"], $storePath);
		
			if(preg_match('/\.(\w*?)$/',$_FILES["file"]["name"], $extname_match)){
				$_FILES["file"]["type"]=$extname_match[1];
			}else
				$_FILES["file"]["type"]='none';
			$fileInfo=array(
				'name'=>$_FILES["file"]["name"],
				'type'=>$_FILES["file"]["type"],
				'size'=>$_FILES["file"]['size'],
				'parent'=>$_SESSION['document']['currentDirID'],
				'path'=>$_SESSION['document']['currentPath']."/".$_FILES["file"]["name"],
				'comment'=>$this->input->post('comment'),
				'uid'=>$_SESSION['id'],
				'username'=>$_SESSION['username'],
				'time'=>$this->config->item('timestamp')
			);
			db_insert('document',$fileInfo,false,$db_replace);
			redirect('document');
		}
	}
}
?>