<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

	/**
	 * @package MantisBT
	 * @copyright Copyright (C) 2002 - 2009  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */
	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );

	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path.'current_user_api.php' );

	auth_ensure_user_authenticated();

	$t_can_manage = access_has_global_level( config_get( 'manage_user_threshold' ) );
	$t_can_see_realname = access_has_project_level( config_get( 'show_user_realname_threshold' ) );
	$t_can_see_email = access_has_project_level( config_get( 'show_user_email_threshold' ) );

	# extracts the user information for the currently logged in user
	# and prefixes it with u_
	$f_user_id = gpc_get_int( 'id', auth_get_current_user_id() );
	$row = user_get_row( $f_user_id );

	extract( $row, EXTR_PREFIX_ALL, 'u' );

	# In case we're using LDAP to get the email address... this will pull out
	#  that version instead of the one in the DB
	$u_email = user_get_email( $u_id, $u_username );

	html_page_top();

$t_votes = Mantis_Vote::get_user_votes();
# get all information for issues ready for display to user
$t_votes_info = array();
foreach($t_votes as $t_vote) {
	$t_issue = bug_get($t_vote['issue_id']);
	$t_project_name = project_get_name($t_issue->project_id);
	$t_votes_info[] = array('vote'=>$t_vote, 'issue'=>$t_issue, 'project_name'=>$t_project_name);
}

?>

<br />
<div align="center">
<table class="width75" cellspacing="1">

	<!-- Headings -->
	<tr>
		<td class="form-title">
			<?php echo lang_get( 'view_account_title' ) ?>
		</td>
	</tr>

	<!-- Username -->
	<tr <?php echo helper_alternate_class() ?>>
		<td class="category" width="25%">
			<?php echo lang_get( 'username' ) ?>
		</td>
		<td width="75%">
			<?php echo $u_username ?>
		</td>
	</tr>

	<!-- Email -->
	<tr <?php echo helper_alternate_class() ?>>
		<td class="category">
			<?php echo lang_get( 'email' ) ?>
		</td>
		<td>
			<?php
				if ( ! ( $t_can_manage || $t_can_see_email ) ) {
					print error_string(ERROR_ACCESS_DENIED);
				} else {
					if ( !is_blank( $u_email ) ) {
						print_email_link( $u_email, $u_email );
					} else {
						echo " - ";
					}
				}
			?>
		</td>
	</tr>

	<!-- Realname -->
	<tr <?php echo helper_alternate_class() ?> valign="top">
		<td class="category">
			<?php echo lang_get( 'realname' ) ?>
		</td>
		<td>
			<?php
				if ( ! ( $t_can_manage || $t_can_see_realname ) ) {
					print error_string(ERROR_ACCESS_DENIED);
				} else {
					echo $u_realname;
				}
			?>
		</td>
	</tr>

	<?php if ( $t_can_manage ) { ?>
	<tr>
		<td colspan="2" class="center">
			<?php print_bracket_link( 'manage_user_edit_page.php?user_id=' . $f_user_id, lang_get( 'manage_user' ) ); ?>
		</td>
	</tr>
	<?php } ?>
</table>
</div>

<br />

<div>
<table class="buglist">
	<caption>
		<?php echo lang_get( 'own_voted' ) ?>
	</caption>
	<thead>
	<tr>
		<th><?php echo lang_get( 'email_bug' ) ?></th>
		<th><?php echo lang_get( 'vote_weight' ) ?></th>
		<th><?php echo lang_get( 'vote_num_voters' ) ?></th>
		<th><?php echo lang_get( 'vote_balance' ) ?></th>
		<th><?php echo lang_get( 'email_project' ) ?></th>
		<th><?php echo lang_get( 'email_status' ) ?></th>
		<th><?php echo lang_get( 'email_summary' ) ?></th>
	</tr>
	</thead>
	<?php
	if (is_array($t_votes_info) && count($t_votes_info)>0){
	?>
	<?php foreach($t_votes_info as $t_vote_info){ ?>
	<tr bgcolor="<?php echo get_status_color( $t_vote_info['issue']->status )?>">
		<td>
			<a href="<?php echo string_get_bug_view_url( $t_vote_info['vote']['issue_id'] );?>"><?php echo bug_format_id( $t_vote_info['vote']['issue_id'] );?></a>
		</td>
		<td class="right">
			<?php echo ($t_vote_info['vote']['weight']>0)?('+'.$t_vote_info['vote']['weight']):$t_vote_info['vote']['weight'] ?>
		</td>
		<td class="right">
			<?php echo $t_vote_info['issue']->votes_num_voters ?>
		</td>
		<td class="right">
			<?php
			$t_balance = $t_vote_info['issue']->votes_positive - $t_vote_info['issue']->votes_negative;
			echo ($t_balance>0)?('+'.$t_balance):$t_balance; 
			?>
		</td>
		<td class="center">
			<?php echo $t_vote_info['project_name']; ?>
		</td>
		<td class="center">
			<?php echo string_attribute( get_enum_element( 'status', $t_vote_info['issue']->status ) ); ?>
		</td>
		<td>
			<?php
			echo string_display_line( $t_vote_info['issue']->summary );
			if ( VS_PRIVATE == $t_vote_info['issue']->view_state ) {
				printf( ' <img src="%s" alt="(%s)" title="%s" />', $t_icon_path . 'protected.gif', lang_get( 'private' ), lang_get( 'private' ) );
			}
			?>
		</td>
	</tr>
	<?php } // endforeach
	} else { ?>
	<tr><td colspan="7" class="center"><?php echo lang_get('no_votes') ?></td></tr>
	<?php } ?>
	<tfoot>
		<tr>
			<td colspan="2">
				<?php echo lang_get( 'votes_used' ) ?>: <?php echo Mantis_Vote::used_votes() ?>
			</td>
			<td colspan="5">
				<?php echo lang_get( 'votes_remain' ) ?>:
				<?php 
				$t_votes_available = Mantis_Vote::available_votes();
				if ( $t_votes_available === Mantis_Vote::UNLIMITED )
				{
					echo lang_get('vote_unlimited');
				}
				else
				{
					echo $t_votes_available;
				}
				?> 
			</td>
		</tr>
	</tfoot>
</table>
</div>
<?php
	html_page_bottom( __FILE__ );
