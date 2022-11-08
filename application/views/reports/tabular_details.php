<?php $this->load->view("partial/header"); ?>

<div id="page_title"><?php echo $title ?></div>

<div id="page_subtitle"><?php echo $subtitle ?></div>

<div id="table_holder">
	<table id="table"></table>
</div>

<div id="report_summary">
	<?php
	foreach($overall_summary_data as $name=>$value)
	{
	?>
		<div class="summary_row"><?php echo $this->lang->line('reports_'.$name). ': '.to_currency($value); ?></div>
	<?php
	}
	?>
</div>

<script type="text/javascript">
	$(document).ready(function()
	{
	 	<?php $this->load->view('partial/bootstrap_tables_locale'); ?>

		
         var details_data = <?php echo json_encode($details_data); ?>;
		<?php
		if($this->config->item('customer_reward_enable') == TRUE && !empty($details_data_rewards))
		{
		?>
			var details_data_rewards = <?php echo json_encode($details_data_rewards); ?>;
		<?php
		}
		?>
		var init_dialog = function() {
			<?php
			if(isset($editable))
			{
			?>
				table_support.submit_handler('<?php echo site_url("reports/get_detailed_" . $editable . "_row")?>');
				dialog_support.init("a.modal-dlg");
			<?php
			}
			?>
		};

        //number
    function doOnMsoNumberFormat(cell, row, col){
        var result = "";  
        if (row > 0 && col == 0){
            result = "\\@";  
        }
        return result;  
    }

    //Processing export content, this method can customize the content of a row, column, or even cell, that is, set its value to what you want.
    function DoOnCellHtmlData(cell, row, col, data){
        if(row == 0){
            return data;
        }
            
            //If the annotation column is more than 6 words, only the first 6 words will be displayed through the span tag processing. If the content is exported directly, it will lead to incomplete content. Therefore, the value of title attribute in the span tag with complete content should be replaced.

            <?php 
                if($functionPages == 'detailed_sales' || $functionPages == 'specific_customer' ){ 
                    if($functionPages == 'detailed_sales')
                        echo 'if(col == 7 || col == 8 || col == 9 || col == 10 || col == 11){';
                    if($functionPages == 'specific_customer')
                            echo 'if(col == 6 || col == 7 || col == 8 || col == 9 || col == 10){';
            ?>
                
                    var spanObj = $(data);//Convert a string labeled <span title="val"> </span> to a jQuery object
                    var title = spanObj.attr("title");//Read the value of title attribute in <span title="val"</span>.
                    //var span = cell[0].firstElementChild; // Read the first element under the first value in the cell array
                    if(typeof(title) != 'undefined'){
                        return title;
                    }
                }
            <?php } 
            
            
            ?>

        return data;
    }

		$('#table').bootstrapTable({
			columns: <?php echo transform_headers($headers['summary'], TRUE); ?>,
			stickyHeader: true,
			pageSize: <?php echo $this->config->item('lines_per_page'); ?>,
			striped: true,
			pagination: true,
			sortable: true,
			// showColumns: true,
			uniqueId: 'id',
			showExport: true,
			exportDataType: 'all',
			exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel', 'pdf'],
            exportOptions:{  
                                fileName: 'User list',  //File Name Settings  
                                worksheetName: 'sheet1',  //Table workspace name  
                                tableName: 'User list',
                                onCellHtmlData: DoOnCellHtmlData,
                            },
			data: <?php echo json_encode($summary_data); ?>,
			iconSize: 'sm',
			paginationVAlign: 'bottom',
			detailView: true,
			escape: false,
			onPageChange: init_dialog,
			onPostBody: function(data) {
				dialog_support.init("a.modal-dlg");
                $("table[id=table]> tbody  > tr").each(function(index, tr) { 
                    $(this).find('td').each (function() {
                        var cals = $(this).attr('class');
                        if(cals == 'currency'){

                            const vls = $(this).text();
                            var nmbr = new Intl.NumberFormat('id-ID',  {
                                            style: 'currency',
                                            currency: 'IDR',
                                            }).format(vls)
                            $(this).html("<span class='currency' title='"+vls+"'>"+nmbr+"</span>");
                        }
                    });   
                });
			},
			onExpandRow: function (index, row, $detail) {
				$detail.html('<table></table>').find("table").bootstrapTable({
					columns: <?php echo transform_headers_readonly($headers['details']); ?>,
					data: details_data[(!isNaN(row.id) && row.id) || $(row[0] || row.id).text().replace(/(POS|RECV)\s*/g, '')]
				});

				<?php
				if($this->config->item('customer_reward_enable') == TRUE && !empty($details_data_rewards))
				{
				?>
					$detail.append('<table></table>').find("table").bootstrapTable({
						columns: <?php echo transform_headers_readonly($headers['details_rewards']); ?>,
						data: details_data_rewards[(!isNaN(row.id) && row.id) || $(row[0] || row.id).text().replace(/(POS|RECV)\s*/g, '')]
					});
				<?php
				}
				?>
			},
            onLoadSuccess: function() {
                console.log("success");
            },
		});


		init_dialog();
	});
</script>

<?php $this->load->view("partial/footer"); ?>
