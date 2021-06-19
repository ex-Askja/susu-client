function getValue(elem) {
    let e = $(elem).val();

    return (e.length ? e : null);
}

const application = {
    data() {
        return {
            university: {},
            edu: {}
        }
    },
    methods: {
        request: function(method, data, callback) {
            data['method'] = method;

            $.ajax({
                method: 'POST',
                url: 'api.php',
                data: data,
                dataType: 'json',
                success: function(response) {
                    callback(response);
                }
            })
        },
        login: function() {
            this.request('_login', {
                userName: getValue('#userName'),
                userPassword: getValue('#userPassword')
            }, (response) => {
                console.log(response);
            });
        }
    }
};

Vue.createApp(application).mount(".askja-app");