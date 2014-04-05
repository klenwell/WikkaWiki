/*
 *
 *
 */
$(document).ready(function() {
    console.debug('onload running');
    $('#handler-content').addClass('well well-large');
    $('#comments').addClass('well well-large');
    $('#page-controls').addClass('well well-small');
    $('.comment-layout-1').addClass('well');
    $('.comment-layout-2').addClass('well');
    
    // alerts
    $('.success').addClass('alert alert-success');
    $('.error').addClass('alert alert-error');
    $('.usersettings_info').addClass('alert alert-info');
    
    // floats
    $('.floatl').addClass('pull-left well');
    $('.floatr').addClass('pull-right well');
    
    // tables (note: addClass checks for redundancies. See
    // http://stackoverflow.com/a/7403519/1093087)
    $('table').addClass('table table-bordered')
    
    // %%...%% code block fix (removes div.code tags wrapping pre)
    $('div.code > pre').unwrap();        
    
    // logout links
    $('.logout-click').click(function() {
      $("form#form_bootstrap-logout").submit();
      return false;   // avoids following link
    });
    
    // increase default editor min height
    if ( typeof varWikkaEdit !== 'undefined' ) {
        varWikkaEdit.EDITOR_MIN_HEIGHT = 220;
    }
});