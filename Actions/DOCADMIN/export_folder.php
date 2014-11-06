<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

include_once ("FDL/exportfld.php");
function export_folder(Action $action)
{
    
    $exportInvisibleVisibilities = true;
    exportfld($action, 0, "", "", $exportInvisibleVisibilities);
}
