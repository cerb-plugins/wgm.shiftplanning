<h2>{'wgm.shiftplanning.common'|devblocks_translate}</h2>

<div id="configShiftPlanningTabs">
	<ul>
		<li><a href="#tabShiftPlanningAuth">API Credentials</a></li>
		<li><a href="#tabShiftPlanningEmployees">Employees</a></li>
	</ul>
	
	<div id="tabShiftPlanningAuth">
		<form action="javascript:;" method="post" id="frmSetupShiftPlanning" onsubmit="return false;">
		<input type="hidden" name="c" value="config">
		<input type="hidden" name="a" value="handleSectionAction">
		<input type="hidden" name="section" value="shiftplanning">
		<input type="hidden" name="action" value="saveJson">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<b>API Key:</b><br>
		<input type="text" name="api_key" value="{$params.api_key}" size="64" placeholder="e.g. a1b2c3d4e5f6"><br>
		<br>
		<b>Username:</b><br>
		<input type="text" name="sp_user" value="{$params.sp_user}" size="64" placeholder="e.g. Cerb"><br>
		<br>
		<b>Password:</b><br>
		<input type="password" name="sp_password" value="{$params.sp_password}" size="32"><br>
		<br>
		
		<div class="status"></div>
	
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
		
		</form>
	</div>
	
	<div id="tabShiftPlanningEmployees">
		<form action="javascript:;" method="post" id="frmShiftPlanningEmployees" onsubmit="return false;">
		<input type="hidden" name="c" value="config">
		<input type="hidden" name="a" value="handleSectionAction">
		<input type="hidden" name="section" value="shiftplanning">
		<input type="hidden" name="action" value="saveEmployeesJson">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<button type="button" class="sync"><span class="glyphicons glyphicons-refresh"></span> {'common.synchronize'|devblocks_translate|capitalize}</button>
		
		<div id="divShiftPlanningEmployeesToWorkers" style="margin:10px 0px 10px 0px;">
			{include file="devblocks:wgm.shiftplanning::setup/employee_to_worker.tpl"}
		</div>
		
		<div class="status"></div>
		
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
		
		</form>
	</div>
</div>

<script type="text/javascript">
$('#configShiftPlanningTabs').tabs();

$('#frmSetupShiftPlanning BUTTON.submit')
	.click(function(e) {
		genericAjaxPost('frmSetupShiftPlanning','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmSetupShiftPlanning div.status', $o.error);
			} else {
				Devblocks.showSuccess('#frmSetupShiftPlanning div.status', $o.message, true);
			}
		});
	})
;

$('#frmShiftPlanningEmployees BUTTON.sync')
	.click(function() {
		$('#divShiftPlanningEmployeesToWorkers').text('Loading...');
		
		genericAjaxGet('divShiftPlanningEmployeesToWorkers', 'c=config&a=handleSectionAction&section=shiftplanning&action=syncEmployees');
	})
;

$('#frmShiftPlanningEmployees BUTTON.submit')
	.click(function(e) {
		genericAjaxPost('frmShiftPlanningEmployees','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmShiftPlanningEmployees div.status', $o.error);
			} else {
				Devblocks.showSuccess('#frmShiftPlanningEmployees div.status', $o.message, true);
			}
		});
	})
;
</script>
