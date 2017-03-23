<?php

require_once MYBB_ROOT . "inc/class_formcreator.php";

$page->add_breadcrumb_item("Form Creator", "index.php?module=config-formcreator");


$sub_tabs['formcreator_forms'] = array(
    'title' => 'View Forms',
    'link' => 'index.php?module=config-formcreator',
    'description' => 'View all forms created for this website');
$sub_tabs['formcreator_add'] = array(
    'title' => 'Create New Form',
    'link' => 'index.php?module=config-formcreator&amp;action=add',
    'description' => 'Create a new form for this website');

switch ($mybb->get_input('action')) {
    case 'edit':
        $sub_tabs['formcreator_edit'] = array(
            'title' => 'Edit Form',
            'link' => 'index.php?module=config-formcreator&amp;action=edit&amp;formid=' . $mybb->input['formid'],
            'description' => "Change the settings of the form");
        break;
}

if ($mybb->get_input('action') == 'a') {
    $page->add_breadcrumb_item($lang->trashbin, "");
    $page->output_header($lang->trashbin);
    $page->output_nav_tabs($sub_tabs, 'formcreator_');


} elseif ($mybb->get_input('action') == 'add' || $mybb->get_input('action') == 'edit') {

    $formcreator = new formcreator();

    if ($mybb->get_input('action') == 'edit') {
        if ($formcreator->get_form($mybb->input['formid']) == false) {
            flash_message("The form you tried to edit doesn't exist!", 'error');
            admin_redirect("index.php?module=config-formcreator");
        }

        $form = new Form("index.php?module=config-formcreator&amp;action=edit&amp;formid=" . $formcreator->formid, "post");
    } else {
        $form = new Form("index.php?module=config-formcreator&amp;action=add", "post");
    }

    if ($mybb->request_method == "post") {
        $formcreator->load_data($mybb->input);

        $formcreator->clear_error();

        if (empty($formcreator->name)) {
            $formcreator->add_error("Form Name is empty!");
        }

        if (empty($formcreator->allowedgid)) {
            $formcreator->add_error("There were no allowed groups selected!");
        }

        if ($mybb->get_input('action') == 'add') {
            if ($error = $formcreator->is_error()) {
                $page->extra_messages[] = array("type" => "error", "message" => $error);
            } else {
                if ($formid = $formcreator->insert_form()) {
                    flash_message("The form is added succesfully. You can now configure fields.", 'success');
                    admin_redirect("index.php?module=config-formcreator");
                } else {
                    flash_message("Oops something went wrong!", 'error');
                    admin_redirect("index.php?module=config-formcreator");
                }
            }
        } elseif ($mybb->get_input('action') == 'edit') {
            if ($error = $formcreator->is_error()) {
                $page->extra_messages[] = array("type" => "error", "message" => $error);
            } else {
                if ($formcreator->update_form()) {
                    flash_message("The form is edited succesfully.", 'success');
                    admin_redirect("index.php?module=config-formcreator");
                } else {
                    flash_message("Oops something went wrong!", 'error');
                    admin_redirect("index.php?module=config-formcreator");
                }
            }
        } else {
            flash_message("Oops something went wrong!", 'error');
            admin_redirect("index.php?module=config-formcreator");
        }
    }

    if ($mybb->get_input('action') == 'add') {
        $page->add_breadcrumb_item("Add Form", "");
        $page->output_header("Add Form");
        $page->output_nav_tabs($sub_tabs, 'formcreator_add');
    } elseif ($mybb->get_input('action') == 'edit') {
        $page->add_breadcrumb_item("Edit Form", "");
        $page->output_header("Edit Form");
        $page->output_nav_tabs($sub_tabs, 'formcreator_edit');
    }

    $form_container = new FormContainer("Create a new Form");
    $form_container->output_row("Form Name <em>*</em>", "The title of the form", $form->generate_text_box('name', $formcreator->name, array('id' => 'name')),
        'name');
    $form_container->output_row("Allowed Groups <em>*</em>", "Which groups are allowed to use this form", $form->generate_group_select("allowedgid[]", $formcreator->
        allowedgid, array("multiple" => true)));
    $form_container->output_row("Status <em>*</em>", "Is this form active yes or no?", $form->generate_yes_no_radio("active", $formcreator->active));
    $form_container->end();

    $form_container = new FormContainer("Process Options");
    $form_container->output_row("Send PM to user(s)",
        "Send a PM to the User IDs defined here. If you do not want to trigger a PM leave this empty. Multiple users comma seperated.", $form->
        generate_text_box("pmusers", $formcreator->pmusers));
    $form_container->output_row("Send PM to Groups",
        "Send a PM to the Users within the selected groups. If you do not want to trigger a group PM select nothing.", $form->generate_group_select("pmgroups[]",
        $formcreator->pmgroups, array("multiple" => true)));
    $form_container->output_row("Post within forum", "Create a Post within the selected forum", $form->generate_forum_select("fid", $formcreator->fid,
        array('main_option' => "- DISABLED -"), true));
    $form_container->output_row("Send Mail to",
        "Send a mail to the following E-mail address(es). Leave empty if you don't like to send a email. One address per line.", $form->generate_text_area("mail",
        $formcreator->mail));
    $form_container->end();

    if($mybb->get_input('action') == 'edit'){
        $buttons[] = $form->generate_submit_button("Update Form");
    }else{
        $buttons[] = $form->generate_submit_button("Create Form");
    }
    $form->output_submit_wrapper($buttons);
    $form->end();


} else {

    $page->output_header("Form Creator");
    $page->output_nav_tabs($sub_tabs, 'formcreator_forms');

    $table = new Table;
    $table->construct_header("Form name", array());
    $table->construct_header("Active", array());
    $table->construct_header("Link / URL", array());
    $table->construct_header("", array());

    $numquery = $db->simple_select('fc_forms', '*', '');
    $total = $db->num_rows($numquery);

    if ($mybb->input['page']) {
        $pagenr = intval($mybb->input['page']);
        $pagestart = (($pagenr - 1) * 10);

        if ((($pagenr - 1) * 10) > $total) {
            $pagenr = 1;
            $pagestart = 0;
        }
    } else {
        $pagenr = 1;
        $pagestart = 0;
    }

    $query = $db->simple_select('fc_forms', '*', '', array(
        "order_by" => "formid",
        "order_dir" => "DESC",
        "limit_start" => $pagestart,
        "limit" => 10));

    if (!$db->num_rows($query)) {
        $table->construct_cell('<div align="center">No forms</div>', array('colspan' => 4));
        $table->construct_row();
        $table->output("Forms");
    } else {
        while ($form = $db->fetch_array($query)) {

            $table->construct_cell($form['name']);
            if ($form['active'] == 0) {
                $active = "No";
            } elseif ($form['active'] == 1) {
                $active = "Yes";
            }
            $table->construct_cell($active);
            $table->construct_cell("<a href='" . $mybb->settings['bburl'] . "/form.php?formid=" . $form['formid'] . "'>" . $mybb->settings['bburl'] .
                "/form.php?formid=" . $form['formid'] . "</a>");

            $popup = new PopupMenu("form_{$form['formid']}", $lang->options);
            $popup->add_item("Edit Form", "index.php?module=config-formcreator&amp;action=edit&amp;formid=" . $form['formid']);

            $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

            $table->construct_row();
        }
        $table->output("Forms");

        echo draw_admin_pagination($pagenr, 10, $total, "index.php?module=config-formcreator");
    }
}

$page->output_footer();

?>