<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td style="padding-right:50px;"><b>This ShiftPlanning employee...</b></td>
		<td><b>... is this Cerb worker</b></td>
	</tr>
	
	{foreach from=$params.api_employees item=employee}
	<tr>
		<td>
			<input type="hidden" name="employee_ids[]" value="{$employee.id}">
			{$employee.name}
		</td>
		<td>
			<select name="worker_ids[]">
				<option value=""></option>
				{foreach from=$workers item=worker key=worker_id}
				<option value="{$worker_id}" {if $params.employees_to_workers.{$employee.id} == $worker_id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{/foreach}
</table>