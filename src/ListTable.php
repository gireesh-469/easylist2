<?php
/**
 * @package EasyList2
 */
namespace EasyList2;

use Exception;
use PDO;
use PDOException;
use DateTime;
use EasyList2\Exceptions\EasyListException;

class ListTable
{
    public function pager($page, $formid, $page_sizes, $random){
        
        $start_page = $page->total_records == 0 ? 0 : 1;
        $min = ($page->page - 1) * $page->page_size + $start_page;
        $max = $min + $page->total_pages - $start_page;
        $sizeOptions = "";
        
        if($page_sizes == null){
            $page_sizes = array(10,25,50,100,250);
        }
        
        foreach($page_sizes as $eachSize){
            $selected = ($eachSize ==  $page->page_size) ? "selected" : "";
            $sizeOptions .= "<option value='{$eachSize}' {$selected}>{$eachSize}</option>";
        }
        
        $html = "<div class='custom-pagination'>
					<a href='javascript:void(0)' class='first-page enabled' title='First' data-page='1' onclick='pagination{$random}(1,this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-step-backward'></span>
    				</a>
    				<a href='javascript:void(0)' class='prev-page enabled' title='Previous' data-page='{$page->prev_page}' onclick='pagination{$random}({$page->prev_page},this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-backward'></span>
    				</a>
    				<div class='pagedisplay'>
      					Records {$min} to {$max} (Total {$page->total_records} Results) - Page {$page->page} of {$page->total_pages}
    				</div>
    				<a href='javascript:void(0)' class='next-page enabled' title='Next' data-page='{$page->next_page}' onclick='pagination{$random}({$page->next_page},this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-forward'></span>
    				</a>
    				<a href='javascript:void(0)' class='last-page enabled' title='Last' data-page='{$page->last_page}' onclick='pagination{$random}({$page->last_page},this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-step-forward'></span>
    				</a>
					<select class='page-limit' onchange=paginationBySize{$random}({$page->page},this,{$page->total_records})>{$sizeOptions}</select>
  				</div>";
        
        return $html;
    }
    
    public function jsScripts($random, $formid){
        
        $html = "<script>
                    function pagination{$random}(page, element, page_size, total_records){
                        //var form_id = element.closest('form').id;
                        form_id = '{$formid}';
                        updateHiddenAttribute{$random}('page', page, form_id);
                        updateHiddenAttribute{$random}('page_size', page_size, form_id);
                        updateHiddenAttribute{$random}('total_records', total_records, form_id);
                        document.getElementById(form_id).submit();
                    }
                    function paginationBySize{$random}(page, element,total_records){
                        //var form_id = element.closest('form').id;
                        form_id = '{$formid}';
                        var page_size = element.value;
                        updateHiddenAttribute{$random}('page', page, form_id);
                        updateHiddenAttribute{$random}('page_size', page_size, form_id);
                        updateHiddenAttribute{$random}('total_records', total_records, form_id);
                        document.getElementById(form_id).submit();
                    }
                    function updateHiddenAttribute{$random}(name, value, form){
                        form_id = '{$formid}';
                        if(document.getElementById(form).elements[name]){
                            document.getElementById(form_id).elements[name].value = value;
                        }else{
                            //addHiddenField{$random}(name, value, form);
                            var input = document.createElement('input');
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', name);
                            input.setAttribute('value', value);
                            //append to form element that you want .
                            document.getElementById(form).appendChild(input);
                        }
                    }
                </script>";
        
        return $html;
    }
    
    public function table($data){
        
        
        
    }
}
