<?php
class View{
    
    function generate($content_view, $template_view, $data = null, $setting){
        if(is_array($setting)) {
            extract($setting);
        }
        
        if(is_array($data)) {
            extract($data);
        }
        
        include VIEWSSROOT.'/template/'.$template_view;
    }
    
    function getPrint($data = null){
        if(is_array($data['request'])) {
            extract($data['request']);
        }
        if(file_exists(VIEWSSROOT.'/print.php')){
            ob_start();
            require VIEWSSROOT.'/print.php';
            return ob_get_clean();
        }else{ 
            return 'zxc';
        }
    }

	function admin($content_view, $template_view, $data = null, $setting){
        if(is_array($setting)) {
            extract($setting);
        }
        
        if(is_array($data)) {
            extract($data);
        }

        include VIEWSSROOT.'/admin/template/'.$template_view;
    }
    function getContent($window, $data = null){
        ob_start();
        if(is_array($data)) {
            extract($data);
        }
		
        if($window!='' && file_exists(VIEWSSROOT.'/window/'.$window.'.php'))
            require VIEWSSROOT.'/window/'.$window.'.php';
        else{ 
            echo 'not found';
        }
        
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }
    
    /**
     * Print page content
     * @param string $page
     * @param array $data
     * @return content
     */
    function getPage($page, $data = null){
        ob_start();
        if(is_array($data)) {
            extract($data);
        }
		
        if($page!='' && file_exists(VIEWSSROOT.'/window/'.$page.'.php'))
            require VIEWSSROOT.'/window/'.$page.'.php';
        else{ 
            echo 'Контент не найден';
        }
        
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }

    function getWindow($window, $data = null){
        if(is_array($data)) {
            extract($data);
        }
        include VIEWSSROOT.'/window/'.$window.'.php';
    }
    function block($block, $data = null){
        if(is_array($data)) {
            extract($data);
        }
        include VIEWSSROOT.'/block/'.$block.'.php';
    }
    public function pageing($cnt=null,$limit=null,$num=null,$lnk=null){
        if(!file_exists(VIEWSSROOT.'/pageing.php')) return -1;
        if(null===$cnt) $cnt=$this->elementsCnt;
        if(null===$limit) $limit=$this->contentData['p_limit'];
        $num=null!==$num?--$num:$this->contentData['p_num'];
        if($cnt<=$limit||$cnt<=$limit*$num) return;
        if(null===$lnk) $lnk=str_replace(array('cl_page_cl&','cl_page_cl'),array('?',''),preg_replace('/([\?|\&]cl_page_cl=\d{1,})/ism','cl_page_cl',str_replace(VALN_GET_PAGE,'cl_page_cl',REQUEST_URI)));
        $lnkPage=(false===strpos($lnk,'?')?'?':'&').VALN_GET_PAGE.'=';
        $pageCnt=ceil($cnt/$limit);
        $prev=(bool)$num;
        $next=(bool)((int)$num<(int)$pageCnt-1);
        $prevLnk=$prev?$lnk.(1==$num?'':$lnkPage.$num):'';
        $nextLnk=$next?$lnk.$lnkPage.($num+2):'';
        ++$num;

        $pageLinks=array(1=>(1==$num?true:$lnk));

        $limitNum=6; // Лимит элементов
        $limitNum3=round($limitNum/3);

        // от начала
        if($limitNum>=$num+$limitNum3-1) $s=$num-round($limitNum/2);
        // от конца
        elseif($pageCnt<$num+$limitNum-$limitNum3) $s=$pageCnt-$limitNum;
        // середина && от начала но чуть дальше
        else $s=$num-$limitNum3;

        if(1>$s) $s=1;
        $l=$s-1+$limitNum;
        if($l>=$pageCnt-2) $l=$pageCnt;
        if(4>$s) $s=1;
        if(1==$s) $s=2;

        if(2<$s) $pageLinks[$s-1]=$s-1==$num?true:false;
        for(;$s<=$l;$s++) $pageLinks[$s]=$s==$num?true:$lnk.$lnkPage.$s;
        if($l<$pageCnt){
                ++$l;
                if($l<$pageCnt){$pageLinks[$l]=false;$l=$pageCnt;}
                if($l==$pageCnt) $pageLinks[$pageCnt]=$lnk.$lnkPage.$pageCnt;
        }
        include VIEWSSROOT.'/pageing.php';
    }
    
    public function vim_lock(){
        if (!isset($_COOKIE["admin_users"])||$_COOKIE["admin_users"]=='lock'){
            if (empty($_COOKIE["admin_users"])){
                header('Location: http://'.DOMAIN_MAIN.'/');
            }
        }
    }
}