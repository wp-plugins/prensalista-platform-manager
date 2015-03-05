<?php
function prensalista_session_start()
{
    if(!session_id()) 
    {
        session_start();
    }
}
function prensalista_session_current()
{
    return session_id();
}
function prensalista_session_destroy()
{
    session_destroy ();
}
add_action('init', 'prensalista_session_start', 1);
?>
