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
        if(array_key_exists('column', $data)){
            $headerArr = array();
            $actionBit = 0;
            $tableHtml = '<table class="table table-bordered  table-condensed table-hover tank-core-table">'
                            .'<tbody>'
                                .'<tr>';
            foreach($data['column'] AS $dataHeader){
            $headerArr[] = $dataHeader['column'];
            $tableHtml              .= '<th class="'.((array_key_exists('class', $dataHeader)) ? $dataHeader['class'] : '').'" 
                                            width="'.((array_key_exists('width', $dataHeader)) ? $dataHeader['width'] : '').'" >';
            if(array_key_exists('sort', $dataHeader)){
                $tableHtml              .= ' <a  href="javascript:void(0)" 
                                                 class="sortClass"
                                                 data-sort="'.$dataHeader['sort'].'" 
                                                 data-sort-type="asc" 
                                                 title="Sort">'.$dataHeader['head'].'</a>';
           }else{
                $tableHtml              .= $dataHeader['head'];
           }
           $tableHtml              .= '</th>';
           }
           if(array_key_exists('action', $data)){
                $tableHtml .= '<th class="text-center">Action</th>';
                $actionBit = 1;
           }
           $tableHtml          .= '</tr>';
           
           if(!empty($data['data'])){
            foreach($data['data'] AS $dataTdItems){
                $tableHtml      .= '<tr>';
                $assoArray = (array) $dataTdItems;
                foreach($headerArr AS $eachHeaderColumn){
                    if(array_key_exists($eachHeaderColumn, $assoArray)){ $tableHtml       .= '<td class="text-left">'.$assoArray[$eachHeaderColumn].'</td>';}
                    else{ $tableHtml       .= '<td class="text-left"></td>'; }
                }
                if(array_key_exists('action', $data)){
                    $tableHtml         .= '<td style="min-width:89px;" class="text-center">';
                    $actionItemStr = implode("", $data['action']);
                        preg_match_all('/(?<=\{)(.*?)(?=\})/', $actionItemStr, $matches);
                        foreach($matches[0] AS $eachMatch){
                            if(array_key_exists($eachMatch, $assoArray)){
                                $actionItemStr    = str_replace('{'.$eachMatch.'}', $assoArray[$eachMatch], $actionItemStr); 
                            }else{
                                $actionItemStr    = str_replace('{'.$eachMatch.'}', 0, $actionItemStr); 
                            }
                        }
                        $tableHtml         .= $actionItemStr;
                    $tableHtml         .= '</td>';
                }
                $tableHtml      .= '</tr>';
            }
            
           }else{
            $tableHtml          .= '<tr><td class="warning" colspan="'.(count($headerArr) + $actionBit).'"  style="text-align: center; vertical-align: middle;">No Record Found</td></tr>';
           }


           

           

           $tableHtml       .= '</tbody>'
                        .'</table>';

            return $tableHtml;
        }
        //$header = $data['column'];
        
        
    }
}
