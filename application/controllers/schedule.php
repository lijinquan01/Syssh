<?php
class Schedule extends SS_controller{
	
	var $section_title='日程';
	
	function __construct(){
		$this->default_method='calendar';
		parent::__construct();
		$this->load->model('cases_model','cases');
		$this->load->model('client_model','client');
	}
	
	function calendar(){
		$this->load->model('achievement_model','achievement');
		
		$sidebar_function=$this->company->syscode.'_'.'schedule_side_table';
		
		if(method_exists($this->company, $sidebar_function)){
			$sidebar_tables=$this->company->$sidebar_function();
			$this->load->addViewData('sidebar_tables',$sidebar_tables);
		}
		
		$this->load->view('schedule/calendar');
		
		$this->load->view('schedule/calendar_sidebar',true,'sidebar');
	}
	
	function mine(){
		$this->lists('mine');
	}
	
	function plan(){
		$this->lists('plan');
	}
	
	function lists($method=NULL){
		
		if($this->input->get('case')){
			$this->section_title='日程 - '.$this->cases->fetch($this->input->get('case'),'name');
		}
		

		if($this->input->post('review_selected') && $this->user->isLogged('partner')){
			//在列表中批量审核所选日志
			$this->schedule->review($this->input->post('schedule_check'));
		}
		$field=array(
			'case.id'=>array('heading'=>'案件','cell'=>'{case_name}<p style="font-size:11px;text-align:right;"><a href="#schedule/lists?case={case}">本案日志</a> <a href="#cases/edit/{case}">案件</a></p>'),
		
			'staff_name'=>array('heading'=>array('data'=>'人员','width'=>'60px'),'cell'=>'<a href="#schedule/list?staff={staff}">{staff_name}</a>'),
		
			'name'=>array('heading'=>'标题','eval'=>true,'cell'=>"
				return '<a href=\"javascript:showWindow(\'schedule/edit/{id}\')\" title=\"{name}\">'.str_getSummary('{name}').'</a>';
			"),
		
			'content'=>array('heading'=>'内容','eval'=>true,'cell'=>"
				return '<div title=\"{content}\">'.str_getSummary('{content}').'&nbsp;'.'</div>';
			"),
		
			'schedule_experience'=>array('heading'=>'心得','eval'=>true,'cell'=>"
				return ({review_permission}||\$this->user->id=='{staff}')?'<div title=\"{experience}\">'.str_getSummary('{experience}').'&nbsp;'.'</div>':'-';
			"),
		
			'time_start'=>array('heading'=>array('data'=>'时间','width'=>'60px'),'eval'=>true,'cell'=>"
				return date('m-d H:i',{time_start});
			"),
		
			'hours_own'=>array('heading'=>array('data'=>'时长','width'=>'55px'),'eval'=>true,'cell'=>"
				if('{hours_checked}'==''){
					return '<span class=\"hours_own'.({review_permission}?' editable':'').'\" id={id} name=\"hours\" title=\"自报：{hours_own}\">{hours_own}</span>';
				}else{
					return '<span class=\"hours_checked'.({review_permission}?' editable':'').'\" id={id} name=\"hours\" title=\"自报：{hours_own}\">{hours_checked}</span>';
				}
			"),
		
			'comment'=>array('heading'=>'评语','eval'=>true,'cell'=>"
				if({review_permission}){
					return '<textarea name=\"schedule_list_comment[{id}]\" style=\"width:95%;height:70%\">{comment}</textarea>';
				}else{
					if(\$this->user->id=='{staff}'){
						return '<div title=\"{comment}\">'.str_getSummary('{comment}').'&nbsp;'.'</div>';
					}else{
						return '-';
					}
				}
				
			")
		);
		
		if($method=='mine'){
			unset($field['staff_name']);
		}		

		if($this->input->get('export')=='excel'){
			$this->output->as_ajax=false;
			
			$field=array(
				'name'=>array('heading'=>'标题'),
				'content'=>array('heading'=>'内容'),
				'time_start'=>array('heading'=>'时间','eval'=>true,'cell'=>"return date('Y-m-d H:i',{time_start});"),
				'hours_own'=>array('heading'=>'自报小时'),
				'staff_name'=>array('heading'=>'律师')
			);
			
			$this->table->setFields($field)
				->setData($this->schedule->getList($method))
				->generateExcel();
		}else{
			$tableView=
				$this->table
					->setFields($field)
					->setData($this->schedule->getList($method))
					->generate();
			$this->load->addViewData('list',$tableView);
			$this->load->view('schedule/list');
		}		
	}

	function listWrite(){
		if($this->input->post('schedule_list_comment')){
			foreach($this->input->post('schedule_list_comment') as $id => $comment){
				$schedule_list_comment_return=$this->schedule->setComment($id,$comment);
				
				echo $schedule_list_comment_return['comment'];
				
				$this->user->sendMessage($schedule_list_comment_return['uid'],
		
				$schedule_list_comment_return['comment'].'（日志：'.$schedule_list_comment_return['name'].'收到的点评）',
				'你的日志："'.$schedule_list_comment_return['name'].'"收到点评');
			}
		}
		
		if($this->input->post('schedule_list_hours_checked') || $this->input->post('schedule_list_hours_checked')){
			foreach($this->input->post('schedule_list_hours_checked') as $id => $hours_checked){
				echo $this->schedule->check_hours($id,$hours_checked);
			}
		}
	}
	
	function outPlan(){
		
		$field=Array(
			'staff_name'=>array('heading'=>array('data'=>'人员','width'=>'60px'),'cell'=>'<a href="#schedule/lists?staff={staff}"> {staff_name}</a>'),
		
			'time_start'=>array('heading'=>array('data'=>'时间','width'=>'60px'),'eval'=>true,'cell'=>"
				return date('m-d H:i',{time_start});
			"),
		
			'place'=>array('heading'=>array('data'=>'外出地点','width'=>'25%'))
		);
		
		$table=$this->table->setFields($field)
					->setData($this->schedule->getOutPlanList())
					->generate();
		
		$this->load->addViewData('list',$table);
		
		$this->load->view('list');
	}
	
	function readCalendar($start,$end=NULL){
		if(is_null($end)){
			//获取指定的一个日程
			$this->output->data=$this->schedule->fetch($start);
		
		}else{
			//获得当前视图的全部日历，根据$this->input->get('start'),$this->input->get('end')(timestamp)
			$this->output->data=$this->schedule->fetch_range($start,$end,$this->input->get('staff'),$this->input->get('case'));
		}
	}
	
	function workHours(){

		if(date('w')==1){//今天是星期一
			$start_of_this_week=strtotime($this->date->today);
		}else{
			$start_of_this_week=strtotime("-1 Week Monday");
		}
		
		if(!option('in_date_range')){
			option('date_range/from',date('Y-m-d',$start_of_this_week));
			option('date_range/to',$this->date->today);
			option('date_range/from_timestamp',$start_of_this_week);
			option('date_range/to_timestamp',$this->date->now);
			option('in_date_range',true);
		}
		
		$staffly_workhours=$this->schedule->getStafflyWorkHoursList();

		$chart_staffly_workhours_catogary=json_encode(array_sub($staffly_workhours,'staff_name'));
		$chart_staffly_workhours_series=array(
			array('name'=>'工作时间','data'=>array_sub($staffly_workhours,'sum'))
		);
		$chart_staffly_workhours_series=json_encode($chart_staffly_workhours_series,JSON_NUMERIC_CHECK);

		$field=array(
			'staff_name'=>array('heading'=>'姓名'),
			'sum'=>array('heading'=>'总工作时间'),
			'avg'=>array('heading'=>'工作日平均')
		);
		
		$work_hour_stat=$this->table->setFields($field)
				->generate($staffly_workhours);

		$this->load->addViewArrayData(compact('chart_staffly_workhours_catogary','chart_staffly_workhours_series','work_hour_stat'));
	
		$this->load->view('schedule/workhours');
	}
	
	function writeCalendar($action,$schedule_id=NULL){
		
		if($action=='add'){//插入新的任务
			$data = $this->input->post();
			
			$new_schedule_id = $this->schedule->add($data);
			$this->schedule->updateProfiles($new_schedule_id, $this->input->post('profiles'));
			
			if($new_schedule_id){
				$this->output->status='success';
				$this->output->data=array('id'=>$new_schedule_id,'name'=>$data['name']);
			}
			
		}elseif($action=='delete'){//删除任务
			if($this->schedule->remove($schedule_id)){
				$this->output->status='success';
			}
		
		}elseif($action=='update'){//更新任务内容
			$this->schedule->update($schedule_id,$this->input->post());
			$this->schedule->updateProfiles($schedule_id, $this->input->post('profiles'));
			$this->output->status='success';
			$this->output->data=array('id'=>$schedule_id,'name'=>$this->input->post('name'),'completed'=>$this->input->post('completed'));
		
		}elseif($action=='resize'){//更新任务时间
			$time_delta=intval($this->input->post('dayDelta'))*86400+intval($this->input->post('minuteDelta'))*60;
			
			if($this->schedule->resize($schedule_id,$time_delta,(int)$this->input->post('allDay'))){
				$this->output->status='success';
			}

		}elseif($action=='drag'){
			$time_delta=intval($this->input->post('dayDelta'))*86400+intval($this->input->post('minuteDelta'))*60;

			if($this->schedule->drag($schedule_id,$time_delta,(int)$this->input->post('allDay'))){
				$this->output->status='success';
			}
		}
			
	}
	
	function taskBoard()
	{
		$id = $this->user->id;
		$sort_data = $this -> schedule -> getTaskBoardSort($id);
		$task_board = array();
		
		if(count($sort_data) != 0)	//若查询结果不为空，即在数据库表中获得当前用户的排列方式
		{	//墙的每一列
			foreach ($sort_data as $series)
			{
				$series_array = array();
				
				if(is_array($series))
				{	//每一列的每个任务
					foreach ($series as $task)
					{
						$task_array = array();
						
						$task_id = str_replace('task_' , '' , $task);
						$fetch_result = $this -> schedule -> fetch($task_id);
						if($fetch_result){
							$task_array['id']=$task_id;
							$task_array['title'] = $fetch_result['name'];
							$task_array['content'] = $fetch_result['name'];

							array_push($series_array , $task_array);
						}
					}
				}
				array_push($task_board , $series_array);
			}
		}
		
		$this->load->addViewData('task_board' , $task_board);
		$this->load->view('schedule/taskboard');
	}
	
	function setTaskBoardSort()
	{	//初始化sort_data
		$sort_data = array();
		for($i=0 ; $i<5 ; $i++)
		{
			array_push($sort_data , array());
		}
		//复制对应列至sort_data
		$sort_data_pushed = $this -> input -> post('sortData');
		//echo print_r($sort_data_pushed)."<br/>";
		$key_array = array_keys($sort_data_pushed);
		//echo print_r($key_array)."<br/>";
		foreach ($key_array as $series)
		{
			$sort_data[$series] = $sort_data_pushed[$series];
		}
		//echo print_r($sort_data)."<br/>";
		
		$this -> schedule -> setTaskBoardSort(json_encode($sort_data) , $this->user->id);
		//echo json_encode($sort_data)."<br/>";
		$this -> load -> require_head = false;
		$this->output->status='success';
	}
	
	function addToTaskBoard($task_id , $uid=NULL , $series=NULL)
	{	
		if(is_null($uid)){
			$uid=$this->user->id;
		}
		//单个任务
		$task = "task_".$task_id;
		//取一列任务墙
		$sort_data = $this -> schedule -> getTaskBoardSort($uid);
		
		if(count($sort_data) != 0)
		{	//将任务加入墙的第一列末尾
			if(is_null($series))
			{
				$series = 0;
			}
			if($series > 4)
			{
				$series = 4;
			}
			$each_series = $sort_data[$series];
			array_push($each_series , $task);
			$sort_data[$series] = $each_series;
			
			$this -> schedule -> setTaskBoardSort(json_encode($sort_data), $uid);
		}
		else	//查询结果为空，即数据库表中没有该用户的任务墙记录，则新增一条记录
		{
			$first_series = array();
			array_push($first_series , $task);
			array_push($sort_data , $first_series);
			for($i=0 ; $i<4 ; $i++)
			{
				array_push($sort_data , array());
			}
			
			$this -> schedule -> createTaskBoard(json_encode($sort_data), $uid);
		}
		
		$this->output->status='success';
		$this->output->message('已添加至“任务”');
	}
	
	function deleteFromTaskBoard($task_id , $uid=NULL)
	{
		if(is_null($uid))
		{
			$uid=$this->user->id;
		}
		//要删除的任务
		$task = "task_".$task_id;
		//遍历sort_data
		$sort_data = $this -> schedule -> getTaskBoardSort($uid);
		//echo "sort_data = "; echo print_r($sort_data)."<br/>";
		for ($i=0 ; $i<5 ; $i++)
		{
			$series = $sort_data[$i];
			$key = array_search($task , $series);
			//echo "key = "; echo print_r($key)."<br/>";
			if($key!==false && $key!=="")
			{
				echo "after delete"."<br/>";
				$sort_data[$i] = array();
				$series[$key] = NULL;
				//echo "series = "; echo print_r($series)."<br/>";
				$series = array_filter($series);
				//echo "series = "; echo print_r($series)."<br/>";
				foreach ($series as $value)
				{
					array_push($sort_data[$i] , $value);
				}
				//echo "sort_data = "; echo print_r($sort_data)."<br/>";
				$this -> schedule -> setTaskBoardSort(json_encode($sort_data), $uid);
				break;
			}
		}
		
		$this->output->status='success';
	}
	
	function add(){
		$this->output->setData('新日程', 'name');
		$this->load->addViewData('mode', 'add');
		$this->load->view('schedule/edit');
	}
	
	function view($schedule_id){
		$this->edit($schedule_id,'view');
	}
	
	/**
	 * ajax响应页面，载入dialog内单条日程视图
	 */
	function edit($schedule_id=NULL,$mode='edit'){
		
		if(isset($schedule_id)){
			$this->schedule->id=$schedule_id;

			$schedule=$this->schedule->fetch($schedule_id);
			
			$profiles=$this->schedule->getProfiles($schedule_id);

			if(isset($schedule['case'])){
				$case=$this->cases->fetch($schedule['case']);
				$case_name=strip_tags($case['name']);
				$this->load->addViewData('case_name', $case_name);
			}

			$this->load->addViewData('schedule', $schedule);
			$this->load->addViewData('profiles', $profiles);

			isset($schedule['name']) && $this->section_title=$schedule['name'];

			isset($schedule['completed']) && $this->output->setData($schedule['completed'],'completed');
		}
		
		$this->output->setData($this->load->view("schedule/$mode",true));
	}
}
?>