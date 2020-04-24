var API_BASE = ''
$(function() {
    $('.login-btn').click(function() {
        var account = $.trim($('#account').val());
        var pwd = $('#password').val();
        login(account, pwd).then(function(res) {
            console.log(res)
        });
    });
    $('.register-btn').click(function() {
        window.location.href = '/register.html'
    });

    function login(account, pwd) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: API_BASE + '/api/User/login',
                data: {
                    account: account,
                    password: pwd
                },
                success: function(res) {
                    resolve(res)
                },
                error: function(err) {
                    reject(err)
                }
            })
        })
    }
});