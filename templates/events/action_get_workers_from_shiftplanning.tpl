<b>With schedule:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[schedule_id]">
	<option value=""> - {'common.any'|devblocks_translate|capitalize} - </option>
	{foreach from=$schedules item=schedule}
	<option value="{$schedule.id}" {if $schedule.id == $params.schedule_id}selected="selected"{/if}>{$schedule.name}</option>
	{/foreach}
</select>
</div>

<b>Save results to worker list variable:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[var_key]">
	<option value=""></option>
	{foreach from=$variables_workers item=var key=var_key}
	<option value="{$var_key}" {if $var_key == $params.var_key}selected="selected"{/if}>{$var.label}</option>
	{/foreach}
</select>
</div>
