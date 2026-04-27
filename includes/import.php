<?php

function ksf_fa_pm_import_menu()
{
    add_menu_entry('project_import', 'Import Projects', '', 'project_import');
}

function ksf_render_pm_import()
{
    $target_fields = [
        'project_no',
        'name',
        'description',
        'customer',
        'start_date',
        'due_date',
        'status',
        'priority',
        'assigned_to',
        'hours',
        'budget',
    ];

    $processor = function($row) {
        global $db;
        include_once INCLUDES . '/db.inc';
        
        $project_no = $row['project_no'] ?? '';
        if (empty($project_no)) return false;
        
        $check = db_fetch_assoc(db_query(
            "SELECT project_no FROM " . TB_PREF . "projects WHERE project_no = " . db_escape($project_no)
        ));
        
        if ($check) {
            $sets = [];
            foreach ($row as $f => $v) {
                if ($f !== 'project_no') $sets[] = "$f = " . db_escape($v);
            }
            db_query("UPDATE " . TB_PREF . "projects SET " . implode(', ', $sets) . " WHERE project_no = " . db_escape($project_no));
        } else {
            $cols = implode(', ', array_keys($row));
            $vals = implode(', ', array_map(fn($v) => db_escape($v), array_values($row)));
            db_query("INSERT INTO " . TB_PREF . "projects ($cols) VALUES ($vals)");
        }
        
        return ['project_no' => $project_no];
    };
    
    return ksf_render_import_page('project', $target_fields, $processor);
}

add_hook('ksf_fa_pm_install', 'ksf_fa_pm_import_menu');