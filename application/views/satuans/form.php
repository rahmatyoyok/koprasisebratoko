<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('satuans/save_definition/'.$satuan_id, array('id'=>'attribute_form', 'class'=>'form-horizontal')); ?>
<fieldset id="attribute_basic_info">

	<div class="form-group form-group-sm">
		<?php echo form_label($this->lang->line('satuans_satuan_name'), 'satuan_name', array('class' => 'control-label col-xs-3')); ?>
		<div class='col-xs-8'>
			<?php echo form_input(array(
					'name'=>'satuan_name',
					'class'=>'form-control input-sm',
					'value'=>$satuan_info->satuan_name)
			);?>
		</div>
	</div>

</fieldset>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{
	var values = [];
	var satuan_id = <?php echo $satuan_id; ?>;
	var is_new = satuan_id == -1;

	var disable_satuan_types = function()
	{
		var satuan_type = $("#satuan_type option:selected").text();

		if(satuan_type == "DATE" || (satuan_type == "GROUP" && !is_new) || satuan_type == "DECIMAL")
		{	 
			$('#satuan_type').prop("disabled",true);	
		} 
		else if(satuan_type == "DROPDOWN")
		{
			$("#satuan_type option:contains('GROUP')").hide();
			$("#satuan_type option:contains('DATE')").hide();
			$("#satuan_type option:contains('DECIMAL')").hide();
		}
		else
		{
			$("#satuan_type option:contains('GROUP')").hide();
		}
	}
	disable_satuan_types();
	
	var show_hide_fields = function(event)
	{
	    var is_dropdown = $('#satuan_type').val() !== '1';
	    var is_decimal = $('#satuan_type').val() !== '2';
	    var is_no_group = $('#satuan_type').val() !== '0';

		$('#satuan_value, #satuan_list_group').parents('.form-group').toggleClass('hidden', is_dropdown);
		$('#satuan_unit').parents('.form-group').toggleClass('hidden', is_decimal);
		$('#satuan_flags').parents('.form-group').toggleClass('hidden', !is_no_group);
	};

	$('#satuan_type').change(show_hide_fields);
	show_hide_fields();

	$('.selectpicker').each(function () {
		var $selectpicker = $(this);
		$.fn.selectpicker.call($selectpicker, $selectpicker.data());
	});

	var remove_attribute_value = function()
	{
		var value = $(this).parents("li").text();

		if (is_new)
		{
			values.splice($.inArray(value, values), 1);
		}
		else
		{
			$.post('<?php echo site_url($controller_name . "/delete_attribute_value/");?>' + value, {satuan_id: satuan_id});
		}
		$(this).parents("li").remove();
	};

	var add_attribute_value = function(value)
	{
		var is_event = typeof(value) !== 'string';

        if ($("#satuan_value").val().match(/(\||:)/g) != null)
        {
            return;
        }

		if (is_event)
		{
			value = $('#satuan_value').val();

			if (!value)
			{
				return;
			}

			if (is_new)
			{
				values.push(value);
			}
			else
			{
				$.post('<?php echo site_url("satuans/save_attribute_value/");?>' + value, {satuan_id: satuan_id});
			}
		}

		$('#satuan_list_group').append("<li class='list-group-item'>" + value + "<a href='javascript:void(0);'><span class='glyphicon glyphicon-trash pull-right'></span></a></li>")
			.find(':last-child a').click(remove_attribute_value);
		$('#satuan_value').val('');
	};

	$('#add_attribute_value').click(add_attribute_value);

	$('#satuan_value').keypress(function (e) {
		if (e.which == 13) {
			add_attribute_value();
			return false;
		}
	});

	var satuan_values = <?php echo json_encode($satuan_values) ?>;
	$.each(satuan_values, function(index, element) {
		add_attribute_value(element);
	});

	$.validator.addMethod('valid_chars', function(value, element) {
        return value.match(/(\||_)/g) == null;
	}, "<?php echo $this->lang->line('satuans_attribute_value_invalid_chars'); ?>");

	$('form').bind('submit', function () {
		$(this).find(':input').prop('disabled', false);
	});

	$('#attribute_form').validate($.extend({
		submitHandler: function(form)
		{
			$(form).ajaxSubmit({
				beforeSerialize: function($form, options) {
					is_new && $('<input>').attr({
						id: 'satuan_values',
						type: 'hidden',
						name: 'satuan_values',
						value: JSON.stringify(values)
					}).appendTo($form);
				},
				success: function(response)
				{
					dialog_support.hide();
					table_support.handle_submit('<?php echo site_url($controller_name); ?>', response);
				},
				dataType: 'json'
			});
		},
		rules:
		{
			satuan_name: 'required',
			satuan_value: 'valid_chars',
			satuan_type: 'required'
		},
        messages:
        {
            satuan_name: "<?php echo $this->lang->line('satuans_satuan_name_required'); ?>",
            satuan_type: "<?php echo $this->lang->line('satuans_satuan_type_required'); ?>"
        }
	}, form_support.error));
});
</script>
