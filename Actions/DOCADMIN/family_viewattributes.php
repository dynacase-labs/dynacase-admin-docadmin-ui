<?php
/*
 * @author Anakeen
 * Display family attributes
*/

include_once ("FDL/Class.Doc.php");
include_once ("FDL/Class.DocAttr.php");
include_once ("FDL/Lib.Dir.php");

function familyViewAttributes(Action & $action)
{
    $usage = new ActionUsage($action);
    $famid = $usage->addRequiredParameter("id", "Family identifier");
    $usage->verify();
    
    $dbaccess = $action->dbaccess;
    /**
     * @var DocFam $family
     */
    $family = new_Doc($action->dbaccess, $famid);
    if (!$family->isAlive()) {
        $action->exitError(sprintf(___("document %s not found", "docadmin") , $famid));
    } elseif ($family->doctype != 'C') {
        $action->exitError(sprintf(___("document %s is not a family", "docadmin") , $family->getTitle()));
    }
    $action->parent->addJsRef("lib/mustache.js/mustache.min.js");
    $action->parent->addJsRef("lib/jquery/jquery.js");
    $action->parent->addJsRef("lib/jquery-ui/js/jquery-ui.js");
    $action->parent->addJsRef("lib/jquery-dataTables/1.10/js/jquery.dataTables.js");
    $action->parent->addJsRef("DOCADMIN:family_viewattributes.js", true);
    
    $action->parent->addCssRef("css/dcp/jquery-ui.css");
    $action->parent->addCssRef("lib/jquery-dataTables/1.10/css/jquery.dataTables.css");
    $action->parent->addCssRef("lib/jquery-dataTables/1.10/css/dataTables.jqueryui.css");
    $action->parent->addCssRef("DOCADMIN/Layout/family_viewattributes.css");
    
    $action->lay->set("famIcon", $family->getIcon("", 16));
    $fromids = $family->getFromDoc();
    $parents = [];
    foreach ($fromids as $parentId) {
        $docParent = new_doc($action->dbaccess, $parentId);
        if ($docParent->id != $family->id) {
            $parents[$docParent->id] = array(
                'id' => $docParent->id,
                "name" => $docParent->name,
                "title" => $docParent->getTitle(),
                "icon" => $docParent->getIcon("", 16)
            );
        }
    }
    
    $sql = sprintf("select * from docattr where docid in (%s) and usefor != 'Q' and id !~ '^:' order by ordered", implode(',', $fromids));
    $attrs = [];
    simpleQuery($action->dbaccess, $sql, $attrs);
    
    foreach ($attrs as $k => $v) {
        $attrs[$v["id"]] = $v;
        unset($attrs[$k]);
    }
    $ModPostFix=" [*]";
    $oDocAttr = new DocAttr($dbaccess);
    $oAttrs = $family->getAttributes();
    /**
     * @var NormalAttribute $oa
     */
    foreach ($oAttrs as $oa) {
        // Usefor = 'A' => action attributes (deprecated)
        if ($oa->usefor === "Q") continue;
        if (empty($attrs[$oa->id])) {
            
            if ($oa->id === "FIELD_HIDDENS") continue;
            $oDocAttr->id = $oa->id;
            $oDocAttr->type = $oa->type;
            $oDocAttr->docid = $oa->docid;
            $oDocAttr->usefor = $oa->usefor;
            $oDocAttr->ordered = $oa->ordered;
            $oDocAttr->visibility = $oa->visibility;
            $oDocAttr->labeltext = $oa->labelText;
            $oDocAttr->abstract = ($oa->isInAbstract) ? "Y" : "N";
            $oDocAttr->title = ($oa->isInTitle) ? "Y" : "N";
            
            $oDocAttr->needed = ($oa->needed) ? "Y" : "N";
            $oDocAttr->frameid = ($oa->fieldSet->id != "FIELD_HIDDENS") ? $oa->fieldSet->id : '';
            
            $oDocAttr->link = $oa->link;
            $oDocAttr->elink = $oa->elink;
            $oDocAttr->options = $oa->options;
            $oDocAttr->phpfile = $oa->phpfile;
            $oDocAttr->phpfunc = $oa->phpfunc;
            $oDocAttr->phpconstraint = $oa->phpconstraint;
            
            $attrs[$oa->id] = $oDocAttr->getValues();
            $attrs[$oa->id]["direct"] = "undirect";
        } else {
            $attrs[$oa->id]["direct"] = "direct";
            $currentType=$oa->type;
            if (!empty($oa->format)) {
                $currentType.='("'.$oa->format.'")';
            }
            if ($currentType != $attrs[$oa->id]["type"]) {
                $attrs[$oa->id]["type"]=$currentType.$ModPostFix;
            $attrs[$oa->id]["direct"] = "modattr";
            }
            if ($oa->labelText != $attrs[$oa->id]["labeltext"]) {
                $attrs[$oa->id]["labeltext"]=$oa->labelText.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if (!empty($oa->ordered) && $oa->ordered != $attrs[$oa->id]["ordered"]) {
                $attrs[$oa->id]["ordered"]=$oa->ordered.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if ($oa->visibility != $attrs[$oa->id]["visibility"]) {
                $attrs[$oa->id]["visibility"]=$oa->visibility.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if ($oa->options != $attrs[$oa->id]["options"]) {
                $attrs[$oa->id]["options"]=$oa->options.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if (!empty($oa->link) && ($oa->link != $attrs[$oa->id]["link"])) {
                $attrs[$oa->id]["link"]=$oa->link.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if (!empty($oa->elink) && ($oa->elink != $attrs[$oa->id]["elink"])) {
                $attrs[$oa->id]["elink"]=$oa->elink.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if (!empty($oa->phpfunc) && ($oa->phpfunc != $attrs[$oa->id]["phpfunc"])) {
                $attrs[$oa->id]["phpfunc"]=$oa->phpfunc.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
            if (!empty($oa->phpfile) && ($oa->phpfile != $attrs[$oa->id]["phpfile"])) {
                $attrs[$oa->id]["phpfile"]=$oa->phpfile.$ModPostFix;
                $attrs[$oa->id]["direct"] = "modattr";
            }
        }
        if (isset($attrs[$oa->id]["type"])) {
            $attrs[$oa->id]["simpletype"] = strtok($attrs[$oa->id]["type"], "(");
            $attrs[$oa->id]["inherit"] = ($attrs[$oa->id]["docid"] !== $family->id) ? "parent" : "self";
            $attrs[$oa->id]["displayOrder"] = intval($attrs[$oa->id]["ordered"]);
            if ($attrs[$oa->id]["type"] === "menu") {
                $attrs[$oa->id]["displayOrder"]+= 10000000;
            }
            if ($attrs[$oa->id]["type"] === "action") {
                $attrs[$oa->id]["displayOrder"]+= 20000000;
            }
        }
    }
    
    foreach ($oAttrs as $aid => $oa) {
        if ($oa->usefor !== "Q" && $oa->type === "frame") {
            $attrs[$aid]["displayOrder"] = viewFamilyUtil::getDisplayOrder($aid, $oAttrs);
            $oAttrs[$aid]->ordered = $attrs[$aid]["displayOrder"];
        };
    }
    foreach ($oAttrs as $oa) {
        if ($oa->usefor !== "Q" && $oa->type === "tab") {
            $attrs[$oa->id]["displayOrder"] = viewFamilyUtil::getDisplayOrder($oa->id, $oAttrs);
        };
    }
    
    unset($attrs["FIELD_HIDDENS"]);
    foreach ($attrs as & $attr) {
        if (($attr["type"] === "tab" || $attr["type"] === "frame")) {
            $attr["ordered"] = "";
        };
        $attr["pathId"] = viewFamilyUtil::getPathId($oAttrs[$attr["id"]]);
    }
    
    uasort($attrs, 'viewFamilyUtil::reOrderAttr');
    $action->lay->eSetBlockData("ATTRIBUTES", $attrs);
    
    $selectclass = array();
    $tclassdoc = GetClassesDoc($dbaccess, $action->user->id, 0, "TABLE");
    foreach ($tclassdoc as $k => $cdoc) {
        $selectclass[$k]["idcdoc"] = $cdoc["id"];
        $selectclass[$k]["classname"] = $cdoc["title"];
        $selectclass[$k]["name"] = $cdoc["name"];
        $selectclass[$k]["selected"] = ($cdoc["id"] === $family->id) ? "selected" : "";
    }
    $action->lay->esetBlockData("PARENTS", $parents);
    $action->lay->setBlockData("SELECTFAMILY", $selectclass);
    $action->lay->set("famid", $family->id);
    $action->lay->set("develMode", $action->getParam("DOCADMIN_DEVEL") === "Y");
}

class viewFamilyUtil
{
    /**
     * @param BasicAttribute $oa
     *
     * @return string
     */
    public static function getPathId($oa)
    {
        if ($oa->fieldSet && $oa->fieldSet->id !== "FIELD_HIDDENS") {
            return self::getPathId($oa->fieldSet) . " > " . $oa->id;
        } else {
            return $oa->id;
        }
    }
    
    public static function getDisplayOrder($aid, $oAttrs)
    {
        $order = - 1;
        /**
         * @var NormalAttribute $oa
         */
        foreach ($oAttrs as $oa) {
            if ($oa->fieldSet && $oa->fieldSet->id === $aid) {
                if ($oa->ordered > 0) {
                    if ($order === - 1) {
                        $order = $oa->ordered;
                    } else {
                        $order = min($oa->ordered, $order);
                    }
                }
            };
        }
        
        return $order - 0.5;
    }
    /**
     * use to usort attributes
     *
     * @param BasicAttribute $a
     * @param BasicAttribute $b
     *
     * @return int
     */
    public static function reOrderAttr($a, $b)
    {
        if ($a["displayOrder"] == $b["displayOrder"]) {
            return 0;
        }
        if ($a["displayOrder"] > $b["displayOrder"]) {
            return 1;
        }
        return -1;
    }
    
    function getAttributeProfunder($aid, array $attrs)
    {
        if (!$aid) {
            return 0;
        }
        $oa = $attrs[$aid];
        if (!$oa["frameid"]) {
            return 0;
        }
        
        return 1 + getAttributeProfunder($oa["frameid"], $attrs);
    }
}
