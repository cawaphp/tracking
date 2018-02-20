function TrackQueue() {
    /**
     * @type {boolean}
     */
    this.init = true;
    /**
     * @type {{}}
     */
    this.data = {};

    this.googleAnalyticsMapping = {
        'userId': 'userId',
        'campaignId': 'campaignId',
        'campaignName': 'campaignName',
        'campaignSource': 'campaignSource',
        'campaignMedium': 'campaignMedium',
        'campaignKeyword': 'campaignKeyword',
        'campaignContent': 'campaignContent'
    };

    /**
     * @type {boolean}
     */
    this.facebookIsInit = false;

    /**
     * Some facebook event MUST be send after init (Purchase for example)
     *
     * @type {Array}
     */
    this.facebookQueue = [];
}

/**
 *
 */
TrackQueue.prototype.push = function (/* method, args*/ ) {
    for (var i = 0; i < arguments.length; i++) {
        if (typeof arguments[i] === "function") {
            arguments[i]();
        } else {
            var apply = [];
            for (var j = 0; j < arguments[i].length; j++) {
                apply.push(arguments[i][j]);
            }

            if (typeof this[arguments[i][0]] === "undefined") {
                throw new Error("Invalid method '" + arguments[i][0] + "' on Track widget");
            }

            this[arguments[i][0]].apply(this, apply.slice(1))
        }
    }
};


/**
 * @param {string} type
 * @param {*} value
 */
TrackQueue.prototype.set = function (type, value) {
    if (typeof(this.data[type]) === "undefined" || this.data[type] !== value) {
        this.data[type] = value;
        this.facebookIsInit = false;

        if (window.ga && this.googleAnalyticsMapping[type]) {
            if (typeof value === 'boolean') {
                window.ga('set', this.googleAnalyticsMapping[type], value === true ? 'true' : 'false');
            } else if (typeof value === 'number') {
                window.ga('set', this.googleAnalyticsMapping[type], value.toString());
            } else {
                window.ga('set', this.googleAnalyticsMapping[type], value);
            }
        }
    }
};

/**
 * @param {string} pixelId
 */
TrackQueue.prototype.facebookInit = function(pixelId) {

    if (this.facebookIsInit) {
        return ;
    }

    if (!window.fbq) {
        return ;
    }

    var advancedMatching = {};

    if (this.data['email']) {
        advancedMatching.em = this.data['email'];
    }

    if (this.data['firstName']) {
        advancedMatching.fn = this.data['firstName'];
    }

    if (this.data['lastName']) {
        advancedMatching.ln = this.data['lastName'];
    }

    if (this.data['phone']) {
        advancedMatching.ph = this.data['phone'];
    }

    if (this.data['gender']) {
        advancedMatching.ge = this.data['gender'];
    }

    if (this.data['birthday']) {
        advancedMatching.db = this.data['birthday'];
    }

    if (this.data['city']) {
        advancedMatching.ct = this.data['city'];
    }

    if (this.data['zipCode']) {
        advancedMatching.zp = this.data['zipCode'];
    }

    if (this.data['state']) {
        advancedMatching.st = this.data['state'];
    }

    window.fbq('init', pixelId, advancedMatching);

    for (var i in this.facebookQueue) {
        window.fbq.apply(window.fbq, this.facebookQueue[i]);
    }

    this.facebookQueue = [];
    this.facebookIsInit = true;
};


/**
 * @param {string} url
 */
TrackQueue.prototype.pageView = function (url) {
    if (window.ga) {
        window.ga('send', 'pageview', url);
    }

    if (window.fbq) {
        window.fbq('track', 'PageView');
    }

    if (window.uetq) {
        window.uetq.push("pageLoad");
    }
};

/**
 * @param {string} category
 * @param {string} action
 * @param {string} label
 * @param {number} value
 */
TrackQueue.prototype.event = function (category, action, label, value) {
    if (window.ga) {
        window.ga('send', 'event', category, action, label, value);
    }

    if (window.fbq) {
        var fbData = {'action': action};
        if (label) {
            fbData.label = label;
        }

        if (value) {
            fbData.value = value;
        }

        window.fbq('trackCustom', category, fbData);
    }

    if (window.uetq) {
        var bingData = {'ec': category, 'ea': action};
        if (label) {
            bingData.el = label;
        }

        if (value) {
            bingData.ev = value;
        }

        window.uetq.push(bingData);
    }
};

/**
 * @param {string} currency
 * @param {*} articles
 */
TrackQueue.prototype.articleImpression = function (currency, articles) {
    this.set('currencyCode', currency);

    if (window.ga) {
        window.ga && window.ga('ec:addImpression', articles);
    }
};

/**
 * @param {string} currency
 * @param {*} article
 */
TrackQueue.prototype.articleDetail = function (currency, article) {
    this.set('currencyCode', currency);

    if (window.ga) {
        window.ga('ec:addProduct', article);
        window.ga('ec:setAction', 'detail');
    }

    if (window.fbq) {
        window.fbq('track', 'ViewContent', {
            content_name: article.name,
            content_category: article.category,
            value: article.price,
            currency: currency,
            content_type: 'product',
            contents: {
                id: article.id,
                quantity: article.quantity,
                item_price: article.price
            }
        });
    }
};

/**
 * @param {string} currency
 * @param {*} article
 */
TrackQueue.prototype.articleAdd = function (currency, article) {
    this.set('currencyCode', currency);

    if (window.ga) {
        window.ga('ec:addProduct', article);
        window.ga('ec:setAction', 'add');
    }

    if (window.fbq) {
        var args = ['track', 'AddToCart', {
            content_name: article.name,
            content_category: article.category,
            value: article.price,
            currency: currency,
            content_type: 'product',
            contents: {
                id: article.id,
                quantity: article.quantity,
                item_price: article.price
            }
        }];

        !this.facebookIsInit ? this.facebookQueue.push(args) : window.fbq.apply(window.fbq, args);
    }
};

/**
 * @param {string} currency
 * @param {*} article
 */
TrackQueue.prototype.articleRemove = function (currency, article) {
    this.set('currencyCode', currency);

    if (window.ga) {
        window.ga('ec:addProduct', article);
        window.ga('ec:setAction', 'remove');
    }
};

/**
 *
 * @param {string} currency
 * @param {*} articles
 * @param {*} options
 */
TrackQueue.prototype.purchaseFunnel = function(currency, articles, options) {
    this.set('currencyCode', currency);

    if (window.ga) {
        for (var i in articles) {
            if (articles.hasOwnProperty(i)) {
                window.ga('ec:addProduct', articles[i]);
            }
        }

        window.ga('ec:setAction', 'checkout', options);
    }
};

/**
 * @param {string} currency
 * @param {*} order
 * @param {*} articles
 * @param {*} promos
 */
TrackQueue.prototype.purchase = function (currency, order, articles, promos) {
    this.set('currencyCode', currency);

    if (window.ga) {
        for (var i in articles) {
            if (articles.hasOwnProperty(i)) {
                window.ga('ec:addProduct', articles[i]);
            }
        }

        for (var i in promos) {
            if (promos.hasOwnProperty(i)) {
                window.ga('ec:addPromo', promos[i]);
            }
        }

        window.ga('ec:setAction', 'purchase', order);
    }

    if (window.fbq) {
        var contents = [], itemCount = 0;

        for (var i in articles) {
            if (articles.hasOwnProperty(i)) {
                contents.push({
                    id: articles[i].id,
                    quantity: articles[i].quantity,
                    item_price: articles[i].price
                });

                itemCount++;
            }
        }

        var args = ['track', 'Purchase', {
            currency: currency,
            value: order.revenue,
            num_items: itemCount,
            content_type: 'product',
            contents: contents
        }];

        !this.facebookIsInit ? this.facebookQueue.push(args) : window.fbq.apply(window.fbq, args);
    }

    if (window.gtag) {
        window.gtag('event', 'conversion', {
            'send_to': this.data.adwordsPurchaseSendTo,
            'value': order.revenue,
            'currency': currency,
            'transaction_id': order.id
        });
    }

    if (window.uetq) {
        window.uetq.push({'gv': order.revenue, 'gc': currency});
    }
};

module.exports = TrackQueue;
