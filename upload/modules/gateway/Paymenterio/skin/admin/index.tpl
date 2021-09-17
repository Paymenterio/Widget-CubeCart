<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
  <div id="paymenterio" class="tab_content">
	<h3>{$TITLE}</h3>
	{$version}
	<fieldset><legend>{$LANG.module.cubecart_settings}</legend>
	  <div><label for="status">{$LANG.common.status}</label><span><input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" /></span></div>
	  <div><label for="position">{$LANG.module.position}</label><span><input type="text" name="module[position]" id="position" class="textbox number" value="{$MODULE.position}" /></span></div>
	  <div>
				<label for="scope">{$LANG.module.scope}</label>
				<span>
					<select name="module[scope]">
      						<option value="both" {$SELECT_scope_both}>{$LANG.module.both}</option>
      						<option value="main" {$SELECT_scope_main}>{$LANG.module.main}</option>
      						<option value="mobile" {$SELECT_scope_mobile}>{$LANG.module.mobile}</option>
    					</select>
				</span>
			</div>
	  <div><label for="default">{$LANG.common.default}</label><span><input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}" /></span></div>
	  <div><label for="description">{$LANG.common.description} *</label><span><input name="module[desc]" id="description" class="textbox" type="text" value="{$MODULE.desc}" /></span></div>
	  	</fieldset>
	<fieldset><legend>{$LANG.paymenterio.settings}</legend>
	  <p>{$LANG.module.3rd_party_settings_desc}</p>
		<div><label for="shop_id">{$LANG.paymenterio.shop_id}</label><span><input name="module[shop_id]" id="shop_id" class="textbox" type="text" value="{$MODULE.shop_id}" /></span></div>
		<div><label for="api_key">{$LANG.paymenterio.api_key}</label><span><input name="module[api_key]" id="api_key" class="textbox" type="password" value="{$MODULE.api_key}" /></span></div>
	</fieldset>
	<p>{$LANG.module.description_options}</p>
  </div>
  {$MODULE_ZONES}
  <div class="form_control"><input type="submit" name="save" value="{$LANG.common.save}" /></div>
  <input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>