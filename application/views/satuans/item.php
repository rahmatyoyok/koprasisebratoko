
<div class="form-group form-group-sm">
	<?php echo form_label($this->lang->line("attributes_satuan_name"), "satuan_name_label", array('class' => 'control-label col-xs-3')); ?>
    <div class='col-xs-8'>
		<?php echo form_dropdown('satuan_name', $satuan_names, -1, array('id' => 'satuan_name', 'class' => 'form-control')); ?>
    </div>

</div>

<script type="text/javascript">
    (function() {
        <?php $this->load->view('partial/datepicker_locale', array('config' => '{ minView: 2, format: "'.dateformat_bootstrap($this->config->item('dateformat') . '"}'))); ?>

        var enable_delete = function() {
            $('.remove_attribute_btn').click(function() {
                $(this).parents('.form-group').remove();
            });
        };

        enable_delete();

        $("input[name*='attribute_links']").change(function() {
            var definition_id = $(this).data('definition-id');
            $("input[name='attribute_ids[" + definition_id + "]']").val('');
        }).autocomplete({
            source: function(request, response) {
                $.get('<?php echo site_url('attributes/suggest_attribute/');?>' + this.element.data('definition-id') + '?term=' + request.term, function(data) {
                    return response(data);
                }, 'json');
            },
            appendTo: '.modal-content',
            select: function (event, ui) {
                event.preventDefault();
                $(this).val(ui.item.label);
            },
            delay: 10
        });

        var definition_values = function() {
            var result = {};
            $("[name*='attribute_links'").each(function() {
                var definition_id = $(this).data('definition-id');
                result[definition_id] = $(this).val();

            });
            return result;
        };

        var refresh = function() {
            var definition_id = $("#satuan_name option:selected").val();
            var attribute_values = definition_values();
            attribute_values[definition_id] = '';
            $('#attributes').load('<?php echo site_url("items/attributes/$item_id");?>', {
                'definition_ids': JSON.stringify(attribute_values)
            }, enable_delete);
        };

        $('#satuan_name').change(function() {
            refresh();
        });
    })();
</script>