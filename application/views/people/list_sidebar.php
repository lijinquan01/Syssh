<form>
	<table class="contentTable search-bar" cellpadding="0" cellspacing="0" align="center">
		<thead><tr><th width="80px">搜索</td></tr></thead>
		<tbody>
			<tr>
				<td><input type="text" name="name" value="<?=option('search/name')?>" placeholder="名称" title="名称" /></td>
			</tr>
			<tr>
				<td>
					<select name="labels[]" data-placeholder="标签" multiple="multiple"><?=options($this->people->getAllLabels(),option('search/labels'))?></select>
				</td>
			</tr>
			<tr>
				<td>
					<select name="team[]" multiple="multiple" data-placeholder="组">
						<?=options($this->team->getArray(array('limit'=>false,'people_type'=>option('search/type')),'name','id'),option('search/team'),NULL,true)?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="submit">
					<button type="submit" name="search" tabindex="0">搜索</button>
					<button type="submit" name="search_cancel" tabindex="1"<?if(is_null(option('search/name')) && !option('search/labels') && !option('search/team')){?> class="hidden"<?}?>>取消</button>
				</td>
			</tr>
		</tbody>
	</table>
</form>