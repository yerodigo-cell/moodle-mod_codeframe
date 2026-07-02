define(['jquery'], function($) {
    return {
        init: function(cmid) {
            // Send a ping immediately and then every 10 seconds.
            var sendPing = function() {
                $.ajax({
                    type: 'POST',
                    url: M.cfg.wwwroot + '/mod/codeframe/ajax_track_time.php',
                    data: {
                        cmid: cmid,
                        sesskey: M.cfg.sesskey
                    }
                });
            };
            sendPing();
            setInterval(sendPing, 10000);
        }
    };
});
