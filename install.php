<?php

$a = \Cetera\Application::getInstance();

$conn = $a->getConn();

$r = $conn->fetchColumn("select count(*) from g_options_plugin where id = ?", array(1));

if (!$r) {
    $conn->executeQuery(
        'INSERT INTO
				g_options_plugin (
					  id,
                      g_wrap_type
				)
				VALUES (?,?)',
        array(
            1,
            "abbr"
        )
    );
}