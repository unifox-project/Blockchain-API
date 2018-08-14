<?php
// MySQLi

global $__db;
$__db=false;
function dbConnect($connect){
	global $__db;
	list($server,$login,$pwd,$name,$charset)=explode(';',$connect)+array(false,false,false,false,false);
	//if(LOG) $id=log::dbQuery(false,'MySQL connect "'.$server.'"');
	$_eventId=log::event('db_connect','main',array('host'=>$server,'user'=>$login,'base'=>$name));
	$__db=new mysqli($server,$login,$pwd,$name);
	if(mysqli_connect_errno()){
		trigger_error('err');
		$__db=new mysqli($server,$login,$pwd,$name);
		if(mysqli_connect_errno()) trigger_error('Errno: '.mysqli_connect_errno().' '.mysqli_connect_error(),E_USER_ERROR);
	}
	dbQuery('SET NAMES '.$charset);
	//if(LOG) $id=log::dbQuery($id);
	log::eventEnd($_eventId);
	return true;
}

function forSQL($mixed='',$maxLength=0){
	global $__db;
	if(is_array($mixed)) return array_map('forSQL',$mixed);
	else{
		$mixed=$__db->real_escape_string(trim($mixed));
		if($maxLength>0) $mixed=cutsStr($mixed,$maxLength,$maxLength);
		return $mixed;
	}
}

function dbQuery($sql){
	global $__db;
	//if(LOG) $id=log::dbQuery(false,$sql);
	$_eventId=log::event('db_query','main',array('query'=>$sql));
	$result=$__db->query($sql);

	if(!$result) trigger_error('Errno: '.$__db->errno.' '.$__db->error."\nQuery: ".$sql,E_USER_ERROR);
	//elseif(LOG) $id=log::dbQuery($id);
	log::eventEnd($_eventId,_getBacktrace(__FILE__,!$result),!$result);
	return $result;
}

function dbSelect($sql){
	$res=dbQuery($sql.' LIMIT 1');
	$return=$res->fetch_assoc();
	$res->close();
	return $return;
}

function dbSelectAll($sql,$cnt=false){
	$return=array();
	$res=dbQuery($sql);
        if($cnt){$q=sql_count();
            while($row=$res->fetch_assoc()){
                $return[]=$row;
            }
            $res->close();
            return array('res' => $return, 'cnt'=> $q);
        }else{
            while($row=$res->fetch_assoc()){
		$return[]=$row;
            }
            $res->close();
            return $return;
        }
}

function sql_count(){
	$cnt= dbQuery('SELECT FOUND_ROWS() as count');
	$cnt=$cnt->fetch_assoc();
	return (int)$cnt['count'];
}

function dbSel($table,$where='',$column='*'){
	if($where!='') $where=' WHERE '.$where;
	$res=dbQuery('SELECT '.$column.' FROM '.$table.$where.' LIMIT 1');
	$return=$res->fetch_assoc();
	$res->close();
	return $return;
}
function dbSelAll($table,$where='',$column='*'){
	if($where!='') $where=' WHERE '.$where;
	$res=dbQuery('SELECT '.$column.' FROM '.$table.$where);
	while($row=$res->fetch_assoc()){
		$return[]=$row;
	}
	$res->close();
	return $return;
}

function dbDelete($table,$where,$limit=NULL){
	$sql='DELETE FROM '.$table.' WHERE '.$where;
	if($limit)$sql.=' LIMIT '.$limit;
	return dbQuery($sql);
}
function dbInsert($table,$data){
	$var=array();
	$val=array();
	foreach($data AS $key=>$value){
		$var[]=$key;
		$val[]=forSQL($value);
	}

	$r=dbQuery('INSERT INTO '.$table.' (`'.implode('`,`',$var).'`) VALUES (\''.implode('\',\'',$val).'\')');
	return $r?dbInsertId():false;
}

function dbInsertMass($table,$col,$mass,$defValue=array()){
	$ins=array();
	foreach($mass AS $mK=>$mV){
		$ins[$mK]=array();
		foreach($col AS $cK=>$cV){
			$ins[$mK][$cV]=forSQL(isset($mV[$cV])?$mV[$cV]:(isset($defValue[$cV])?$defValue[$cV]:''));
		}
		$ins[$mK]='(\''.implode('\',\'',$ins[$mK]).'\')';
	}
	$r=dbQuery('INSERT INTO '.$table.' (`'.implode('`,`',$col).'`) VALUES '.implode(',',$ins));
	return $r?dbInsertId():false;
}
function dbInsertId(){
	global $__db;
	return $__db->insert_id;
}

function dbUpdate($table,$data,$where='',$limit=1){
	$set=array();
	foreach($data AS $key=>$val){$set[]='`'.$key.'`=\''.forSQL($val).'\'';}
	if($where!='') $where=' WHERE '.$where;
	$sql='UPDATE '.$table.' SET '.implode(',',$set).$where;
	if($limit)$sql.=' LIMIT '.$limit;
	return dbQuery($sql);
}
function dbUpdateU($table,$data,$where='',$limit=1){
	$set=array();
	foreach($data AS $key=>$val){$set[]='`'.$key.'`='.$val;}
	if($where!='') $where=' WHERE '.$where;
	$sql='UPDATE '.$table.' SET '.implode(',',$set).$where;
	if($limit)$sql.=' LIMIT '.$limit;
	return dbQuery($sql);
}

function dbCount($table,$where=''){
	if($where!='') $where=' WHERE '.$where;
	$res=dbQuery('SELECT COUNT(1) AS `cnt` FROM '.$table.$where);
	$row=$res->fetch_assoc();
	$res->close();
	return isset($row['cnt'])?$row['cnt']:0;
}
function dbIs($table,$where){
	return (bool)dbCount($table,$where.' LIMIT 1');
}

global $__dbTreeNodeQuery, $__dbTreeBranchQuery;
$__dbTreeNodeQuery=array(); $__dbTreeBranchQuery=array();

function dbTree($table,$fields=null){
	$sid=base::tableIsGlobal($table);
	$fields=($fields?$fields.',':'').'`node_id`,`left`,`right`,`level`';
	return dbQuery('SELECT '.$fields.' FROM '.$table.' '.$sid['where'].' ORDER BY `left` ASC');
}


function dbTreeClear($table){
	$sid=base::tableIsGlobal($table);
	if(!empty($sid[0])) dbDelete($table,$sid[0]);
	if(!dbCount($table)) dbQuery('TRUNCATE '.$table);
	$ins=array('name'=>'','level'=>0,'left'=>1,'right'=>2);
	if(!empty($sid[0])) $ins['SID']=SID;
	dbInsert($table,$ins);
	return true;
}


function dbTreeNode($table,$node_id/*,$fields=null*/){
	//$fields=($fields?$fields.',':'').'`node_id`,`left`,`right`,`level`';
	$sid=base::tableIsGlobal($table);
	return dbSel($table,$sid['and'].'`node_id`=\''.(int)$node_id.'\'');
}


function dbTreeInsert($table,$parent_id,$ins=array(),$after_node_id=0){
	$node=dbTreeNode($table,$after_node_id?$after_node_id:$parent_id/*,'`parent_id`'*/); if(!$node) return false;
	unset($ins['SID'],$ins['parent_id'],$ins['left'],$ins['right'],$ins['level'],$ins['node_id']);
	//TABLE LOCK
	$id=dbInsert($table,$ins); if(!$id) return;
	$sid=base::tableIsGlobal($table);
	if($after_node_id){
		$ins['left']=$node['right']+1;
		$ins['right']=$node['right']+2;
		$ins['level']=$node['level'];
		$parent_id=$node['parent_id'];
		dbUpdateU($table,array(
			'left'=>'CASE WHEN `left`>'.$node['right'].' THEN `left`+2 ELSE `left` END',
			'right'=>'CASE WHEN `right`>'.$node['right'].' THEN `right`+2 ELSE `right` END',
			),$sid['and'].'`right`>'.$node['right'],0);
	}else{
		$ins['left']=$node['right'];
		$ins['right']=$node['right']+1;
		$ins['level']=$node['level']+1;
		dbUpdateU($table,array(
			'left'=>'CASE WHEN `left`>'.$node['right'].' THEN `left`+2 ELSE `left` END',
			'right'=>'CASE WHEN `right`>='.$node['right'].' THEN `right`+2 ELSE `right` END',
			),$sid['and'].'`right`>='.$node['right'],0);
	}
	dbUpdateU($table,array('group_cnt'=>'`group_cnt`+1'),'`node_id`='.$parent_id);
	$upd=array('parent_id'=>$parent_id,'left'=>$ins['left'],'right'=>$ins['right'],'level'=>$ins['level']);
	if(!empty($sql[0])) $upd['SID']=SID;
	dbUpdate($table,$upd,'`node_id`='.$id);
	//TABLE UNLOCK
	return $id;
}

function dbTreeDelete($table,$node_id){
	$node=dbTreeNode($table,$node_id/*,'`parent_id`'*/); if(!$node) return false;
	dbDelete($table,'`left` BETWEEN '.$node['left'].' AND '. $node['right']);
	$deltaId=(($node['right']-$node['left'])+1);
	$sid=base::tableIsGlobal($table);
	dbUpdateU($table,array(
		'left'=>'CASE WHEN `left` > '.$node['left'].' THEN `left` - '.$deltaId.' ELSE `left` END',
		'right'=>'CASE WHEN `right` > '.$node['left'].' THEN `right` - '.$deltaId.' ELSE `right` END',
		),$sid['and'].'`right`>'.$node['right'],0);
	dbQuery('UPDATE `'.$table.'` SET `group_cnt`='.(int)dbCount($table,'`parent_id`='.$node['parent_id']).' WHERE `node_id`='.$node['parent_id']);
	return true;
}

function dbTreeParent($table,$node_id/*,$fields=null*/){
	$node=dbTreeNode($table,$node_id/*,$fields*/); if(!$node) return false;
	$sid=base::tableIsGlobal($table);
	return dbSel($table,$sid['and'].'`node_id`=\''.$node['parent_id'].'\'');
}

function dbTreeParents($table,$node_id,$fields=null){
	$fields=($fields?$fields.',':'').'`parent_id`,`node_id`,`left`,`right`,`level`';
	$node=dbTreeNode($table,$node_id/*,$fields*/); if(!$node) return false;
	$sid=base::tableIsGlobal($table);
	$res=dbQuery('SELECT '.$fields.' FROM '.$table.' WHERE '.$sid['and'].'`left` < '.$node['left'].'&&`right` > '.$node['right'].' ORDER BY `left` ASC'); if(!$res) return;
	return array('chosen'=>$node,'branch'=>$res);
}

function dbTreeNodeReplace($table,$node_id1,$node_id2){
	$llr1=dbTreeNode($table,$node_id1/*,'`parent_id`,`group_cnt`,`obj_cnt`'*/); if(!$llr1) return false;
	$llr2=dbTreeNode($table,$node_id2/*,'`parent_id`,`group_cnt`,`obj_cnt`'*/); if(!$llr2) return false;
	dbUpdate($table,array('parent_id'=>$llr2['parent_id'],'left'=>$llr2['left'],'right'=>$llr2['right'],'level'=>$llr2['level'],'group_cnt'=>$llr2['group_cnt'],'obj_cnt'=>$llr2['obj_cnt']),'`node_id`=\''.$node_id1.'\'');
	dbUpdate($table,array('parent_id'=>$llr1['parent_id'],'left'=>$llr1['left'],'right'=>$llr1['right'],'level'=>$llr1['level'],'group_cnt'=>$llr1['group_cnt'],'obj_cnt'=>$llr1['obj_cnt']),'`node_id`=\''.$node_id2.'\'');
	return true;
}

function dbTreeBranch($table,$node_id,$fields=null,$level=null,$sqlWhere=null){
	global $__dbTreeBranchQuery, $__dbTreeNodeQuery;
	$key=$table.'_'.$node_id;
	if(isset($__dbTreeBranchQuery[$key])){
		$__dbTreeBranchQuery[$key]['branch']->data_seek(0);
		return $__dbTreeBranchQuery[$key];
	}
	//$fields=($fields?$fields.',':'').'`parent_id`,`node_id`,`left`,`right`,`level`';
	$node=isset($__dbTreeNodeQuery[$key])?$__dbTreeNodeQuery[$key]:dbTreeNode($table,$node_id/*,$fields*/); if(!$node) return false;
	if(!isset($__dbTreeNodeQuery[$key])) $__dbTreeNodeQuery[$key]=$node;
	$sid=base::tableIsGlobal($table);
	$level=$level?'`level`<='.($node['level']+$level).'&&':'';
	$sqlWhere=$sqlWhere?' && '.$sqlWhere:'';
	$res=dbQuery('SELECT *'./*$fields.*/', CASE WHEN `left` + 1 < `right` THEN 1 ELSE 0 END AS `nflag` FROM '.$table.' WHERE '.$sid['and'].$level.'`node_id`!=\''.(int)$node_id.'\'&&`left` BETWEEN '.$node['left'].' AND '.$node['right'].$sqlWhere.' ORDER BY `left` ASC'); if(!$res) return;
	$__dbTreeBranchQuery[$key]=array('chosen'=>$node,'branch'=>$res);
	return $__dbTreeBranchQuery[$key];
}

function dbTreeBranchMove($table,$node_id1,$node_id2,$position='after'){
	$llr1=dbTreeNode($table,$node_id1/*,'`parent_id`,`group_cnt`,`obj_cnt`'*/); if(!$llr1) return false;
	$llr2=dbTreeNode($table,$node_id2/*,'`parent_id`,`group_cnt`,`obj_cnt`'*/); if(!$llr2) return false;
	if($llr1['level']!=$llr2['level']||$llr1['parent_id']!=$llr2['parent_id']) return;
	$sid=base::tableIsGlobal($table);
	if('before'==$position){
		if($llr1['left']>$llr2['left']){
			$sql='UPDATE '.$table.' SET '
				.'`right` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `right` - '.($llr1['left'] - $llr2['left']).' '
				.'WHEN `left` BETWEEN '.$llr2['left'].' AND '.($llr1['left'] - 1).' THEN `right` +  '.($llr1['right'] - $llr1['left'] + 1).' ELSE `right` END, '
				.'`left` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `left` - '.($llr1['left'] - $llr2['left']).' '
				.'WHEN `left` BETWEEN '.$llr2['left'].' AND '.($llr1['left'] - 1).' THEN `left` + '.($llr1['right'] - $llr1['left'] + 1).' ELSE `left` END '
				.'WHERE '.$sid['and'].'`left` BETWEEN '.$llr2['left'].' AND '.$llr1['right'];
		}else{
			$sql='UPDATE '.$table.' SET '
				.'`right` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `right` + '.(($llr2['left'] - $llr1['left']) - ($llr1['right'] - $llr1['left'] + 1)).' '
				.'WHEN `left` BETWEEN '.($llr1['right'] + 1).' AND '.($llr2['left'] - 1).' THEN `right` - '.(($llr1['right'] - $llr1['left'] + 1)).' ELSE `right` END, '
				.'`left` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `left` + '.(($llr2['left'] - $llr1['left']) - ($llr1['right'] - $llr1['left'] + 1)).' '
				.'WHEN `left` BETWEEN '.($llr1['right'] + 1).' AND '.($llr2['left'] - 1).' THEN `left` - '.($llr1['right'] - $llr1['left'] + 1).' ELSE `left` END '
				.'WHERE '.$sid['and'].'`left` BETWEEN '.$llr1['left'].' AND '.($llr2['left'] - 1);
		}
	}elseif('after'==$position){
		if($llr1['left']>$llr2['left']){
			$sql='UPDATE '.$table.' SET '
				.'`right` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `right` - '.($llr1['left'] - $llr2['left'] - ($llr2['right'] - $llr2['left'] + 1)).' '
				.'WHEN `left` BETWEEN '.($llr2['right'] + 1).' AND '.($llr1['left'] - 1).' THEN `right` +  '.($llr1['right'] - $llr1['left'] + 1).' ELSE `right` END, '
				.'`left` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `left` - '.($llr1['left'] - $llr2['left'] - ($llr2['right'] - $llr2['left'] + 1)).' '
				.'WHEN `left` BETWEEN '.($llr2['right'] + 1).' AND '.($llr1['left'] - 1).' THEN `left` + '.($llr1['right'] - $llr1['left'] + 1).' ELSE `left` END '
				.'WHERE '.$sid['and'].'`left` BETWEEN '.($llr2['right'] + 1).' AND '.$llr1['right'];
		}else{
			$sql='UPDATE '.$table.' SET '
				.'`right` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `right` + '.($llr2['right'] - $llr1['right']).' '
				.'WHEN `left` BETWEEN '.($llr1['right'] + 1).' AND '.$llr2['right'].' THEN `right` - '.(($llr1['right'] - $llr1['left'] + 1)).' ELSE `right` END, '
				.'`left` = CASE WHEN `left` BETWEEN '.$llr1['left'].' AND '.$llr1['right'].' THEN `left` + '.($llr2['right'] - $llr1['right']).' '
				.'WHEN `left` BETWEEN '.($llr1['right'] + 1).' AND '.$llr2['right'].' THEN `left` - '.($llr1['right'] - $llr1['left'] + 1).' ELSE `left` END '
				.'WHERE '.$sid['and'].'`left` BETWEEN '.$llr1['left'].' AND '.$llr2['right'];
		}
	}else return;
	dbQuery($sql);
	return true;
}

function dbTreeBranchChangeParent($table,$node_id,$to_node_id){
	$llr=dbTreeNode($table,$node_id/*,'`parent_id`'*/); if(!$llr) return false;
	if($llr['parent_id']==$to_node_id) return true;
	$llrTo=dbTreeNode($table,$to_node_id); if(!$llrTo) return false;
	$sid=base::tableIsGlobal($table);
	if($llrTo['left']<$llr['left']&&$llrTo['right']>$llr['right']&&$llrTo['level']<$llr['level']-1){
		$sql='UPDATE '.$table.' SET '
			.'`level` = CASE WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `level`'.sprintf('%+d', -($llr['level']-1)+$llrTo['level']).' ELSE `level` END, '
			.'`right` = CASE WHEN '.'`right` BETWEEN '.($llr['right']+1).' AND '.($llrTo['right']-1).' THEN `right`-'.($llr['right']-$llr['left']+1).' '
			.'WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `right`+'.((($llrTo['right']-$llr['right']-$llr['level']+$llrTo['level'])/2)*2+$llr['level']-$llrTo['level']-1).' ELSE '.'`right` END, '
			.'`left` = CASE WHEN `left` BETWEEN '.($llr['right']+1).' AND '.($llrTo['right']-1).' THEN `left`-'.($llr['right']-$llr['left']+1).' '
			.'WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `left`+'.((($llrTo['right']-$llr['right']-$llr['level']+$llrTo['level'])/2)*2+$llr['level']-$llrTo['level']-1).' ELSE `left` END '
			.'WHERE '.$sid['and'].'`left` BETWEEN '.($llrTo['left']+1).' AND '.($llrTo['right']-1);
	}elseif($llrTo['left']<$llr['left']){
		$sql='UPDATE '.$table.' SET '
			.'`level` = CASE WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `level`'.sprintf('%+d', -($llr['level']-1)+$llrTo['level']).' ELSE `level` END, '
			.'`left` = CASE WHEN `left` BETWEEN '.$llrTo['right'].' AND '.($llr['left']-1).' THEN `left`+'.($llr['right']-$llr['left']+1).' '
			.'WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `left`-'.($llr['left']-$llrTo['right']).' ELSE `left` END, '
			.'`right` = CASE WHEN '.'`right` BETWEEN '.$llrTo['right'].' AND '.$llr['left'].' THEN `right`+'.($llr['right']-$llr['left']+1).' '
			.'WHEN '.'`right` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `right`-'.($llr['left']-$llrTo['right']).' ELSE '.'`right` END '
			.'WHERE '.$sid['and'].'('.'`left` BETWEEN '.$llrTo['left'].' AND '.$llr['right']. ' || '.'`right` BETWEEN '.$llrTo['left'].' AND '.$llr['right'].')';
	}else{
		$sql='UPDATE '.$table.' SET '
			.'`level` = CASE WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `level`'.sprintf('%+d', -($llr['level']-1)+$llrTo['level']).' ELSE `level` END, '
			.'`left` = CASE WHEN `left` BETWEEN '.$llr['right'].' AND '.$llrTo['right'].' THEN `left`-'.($llr['right']-$llr['left']+1).' '
			.'WHEN `left` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `left`+'.($llrTo['right']-1-$llr['right']).' ELSE `left` END, '
			.'`right` = CASE WHEN '.'`right` BETWEEN '.($llr['right']+1).' AND '.($llrTo['right']-1).' THEN `right`-'.($llr['right']-$llr['left']+1).' '
			.'WHEN '.'`right` BETWEEN '.$llr['left'].' AND '.$llr['right'].' THEN `right`+'.($llrTo['right']-1-$llr['right']).' ELSE '.'`right` END '
			.'WHERE '.$sid['and'].'('.'`left` BETWEEN '.$llr['left'].' AND '.$llrTo['right'].' || '.'`right` BETWEEN '.$llr['left'].' AND '.$llrTo['right'].')';
	}
	dbQuery($sql);
	dbQuery('UPDATE '.$table.' SET `parent_id`='.$to_node_id.' WHERE `node_id`='.$node_id);
	dbQuery('UPDATE '.$table.' SET `group_cnt`='.(int)dbCount($table,'`parent_id`='.$llr['parent_id']).' WHERE `node_id`='.$llr['parent_id']);
	dbQuery('UPDATE '.$table.' SET `group_cnt`='.(int)dbCount($table,'`parent_id`='.$node_id).' WHERE `node_id`='.$node_id);
	dbQuery('UPDATE '.$table.' SET `group_cnt`='.(int)dbCount($table,'`parent_id`='.$to_node_id).' WHERE `node_id`='.$to_node_id);
	return true;
}
?>