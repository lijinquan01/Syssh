
	<script type="text/javascript">page.children('section[hash="'+hash+'"]').on('sectionload sectionshow',function(){controller='<?=CONTROLLER?>';affair='<?=$this->section_title?>';method='<?=METHOD?>';username='<?=$this->user->name?>';sysname='<?=$this->company->sysname?>';<?=$this->inner_js?>})</script>