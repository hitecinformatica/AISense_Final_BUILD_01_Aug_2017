<?php
/*
 * interfaces_bridge.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2016 Electric Sheep Fencing, LLC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *
 * 3. All advertising materials mentioning features or use of this software
 *    must display the following acknowledgment:
 *    "This product includes software developed by the pfSense Project
 *    for use in the pfSense® software distribution. (http://www.pfsense.org/).
 *
 * 4. The names "pfSense" and "pfSense Project" must not be used to
 *    endorse or promote products derived from this software without
 *    prior written permission. For written permission, please contact
 *    coreteam@pfsense.org.
 *
 * 5. Products derived from this software may not be called "pfSense"
 *    nor may "pfSense" appear in their names without prior written
 *    permission of the Electric Sheep Fencing, LLC.
 *
 * 6. Redistributions of any form whatsoever must retain the following
 *    acknowledgment:
 *
 * "This product includes software developed by the pfSense Project
 * for use in the pfSense software distribution (http://www.pfsense.org/).
 *
 * THIS SOFTWARE IS PROVIDED BY THE pfSense PROJECT ``AS IS'' AND ANY
 * EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE pfSense PROJECT OR
 * ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 */

##|+PRIV
##|*IDENT=page-interfaces-bridge
##|*NAME=Interfaces: Bridge
##|*DESCR=Allow access to the 'Interfaces: Bridge' page.
##|*MATCH=interfaces_bridge.php*
##|-PRIV

require_once("guiconfig.inc");

if (!is_array($config['bridges']['bridged'])) {
	$config['bridges']['bridged'] = array();
}

$a_bridges = &$config['bridges']['bridged'] ;

function bridge_inuse($num) {
	global $config, $a_bridges;

	$iflist = get_configured_interface_list(false, true);

	foreach ($iflist as $if) {
		if ($config['interfaces'][$if]['if'] == $a_bridges[$num]['bridgeif']) {
			return true;
		}
	}

	return false;
}

if ($_GET['act'] == "del") {
	if (!isset($_GET['id'])) {
		$input_errors[] = gettext("Wrong parameters supplied");
	} else if (empty($a_bridges[$_GET['id']])) {
		$input_errors[] = gettext("Wrong index supplied");
	/* check if still in use */
	} else if (bridge_inuse($_GET['id'])) {
		$input_errors[] = gettext("This bridge cannot be deleted because it is assigned as an interface.");
	} else {
		if (!does_interface_exist($a_bridges[$_GET['id']]['bridgeif'])) {
			log_error("Bridge interface does not exist, skipping ifconfig destroy.");
		} else {
			mwexec("/sbin/ifconfig " . $a_bridges[$_GET['id']]['bridgeif'] . " destroy");
		}

		unset($a_bridges[$_GET['id']]);

		write_config();

		header("Location: interfaces_bridge.php");
		exit;
	}
}

$pgtitle = array(gettext("Interfaces"), gettext("Bridges"));
$shortcut_section = "interfaces";
include("head.inc");
if ($input_errors) {
	print_input_errors($input_errors);
}

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("Interface Groups"), false, "interfaces_groups.php");
$tab_array[] = array(gettext("Wireless"), false, "interfaces_wireless.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("QinQs"), false, "interfaces_qinq.php");
$tab_array[] = array(gettext("PPPs"), false, "interfaces_ppps.php");
$tab_array[] = array(gettext("GREs"), false, "interfaces_gre.php");
$tab_array[] = array(gettext("GIFs"), false, "interfaces_gif.php");
$tab_array[] = array(gettext("Bridges"), true, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
display_top_tabs($tab_array);
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('Bridge Interfaces')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=gettext("Interface"); ?></th>
						<th><?=gettext("Members"); ?></th>
						<th><?=gettext("Description"); ?></th>
						<th><?=gettext("Actions"); ?></th>
					</tr>
				</thead>
				<tbody>
<?php

$i = 0;
$ifdescrs = get_configured_interface_with_descr();

foreach ($a_bridges as $bridge) {
?>
					<tr>
						<td>
							<?=htmlspecialchars(strtoupper($bridge['bridgeif']))?>
						</td>
						<td>
<?php
	$members = explode(',', $bridge['members']);
	$j = 0;
	foreach ($members as $member) {
		if (isset($ifdescrs[$member])) {
			echo $ifdescrs[$member];
			$j++;
		}
		if ($j > 0 && $j < count($members)) {
			echo ", ";
		}
	}
?>
						</td>
						<td>
							<?=htmlspecialchars($bridge['descr'])?>
						</td>
						<td>
							<a class="fa fa-pencil"	title="<?=gettext('Edit interface bridge')?>"	href="interfaces_bridge_edit.php?id=<?=$i?>"></a>
							<a class="fa fa-trash"	title="<?=gettext('Delete interface bridge')?>"	href="interfaces_bridge.php?act=del&amp;id=<?=$i?>"></a>
						</td>
					</tr>
<?php
	$i++;
}
?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="interfaces_bridge_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("Add")?>
	</a>
</nav>

<?php include("foot.inc");
