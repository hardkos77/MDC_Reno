// Generated by CoffeeScript 1.9.3
(function () {
    var Instafeed;

    Instafeed = (function () {
        function Instafeed(params, context) {
            var option, value;
            this.options = {
                target: 'instafeed',
                get: 'popular',
                resolution: 'thumbnail',
                sortBy: 'none',
                links: true,
                mock: false,
                useHttp: false,
                afterLoad: null,
                api: 'old',
            };

            if (typeof params === 'object') {
                for (option in params) {
                    value = params[option];
                    this.options[option] = value;
                }
            }
            this.context = context != null ? context : this;
            this.unique = this._genKey();
        }

        Instafeed.prototype.hasNext = function () {
            return typeof this.context.nextUrl === 'string' && this.context.nextUrl.length > 0;
        };

        Instafeed.prototype.next = function () {
            if (!this.hasNext()) {
                return false;
            }
            return this.run(this.context.nextUrl);
        };

        Instafeed.prototype.run = function (url) {
            var header, instanceName, script;

            if (typeof this.options.accessToken !== 'string') {
                throw new Error("Missing accessToken.");
            }
            if ((this.options.before != null) && typeof this.options.before === 'function') {
                this.options.before.call(this);
            }
            if (typeof document !== "undefined" && document !== null) {
                script = document.createElement('script');
                script.id = 'instafeed-fetcher';
                script.src = url || this._buildUrl();
                header = document.getElementsByTagName('head');
                header[0].appendChild(script);
                instanceName = "instafeedCache" + this.unique;
                window[instanceName] = new Instafeed(this.options, this);
                window[instanceName].unique = this.unique;
            }
            return true;
        };

        Instafeed.prototype.parse = function (response) {

            var anchor, childNodeCount, childNodeIndex, childNodesArr, e, eMsg, fragment, header, htmlString, httpProtocol, i, image, imageString, imageUrl, images, img, imgOrient, videoURL, instanceName, j, k, len, len1, len2, node, parsedLimit, reverse, sortSettings, targetEl, tmpEl;
            if (typeof response !== 'object') {
                if ((this.options.error != null) && typeof this.options.error === 'function') {
                    this.options.error.call(this, 'Invalid JSON data');
                    return false;
                } else {
                    throw new Error('Invalid JSON response');
                }
            }

            // if (response.meta.code !== 200) {
            //     if ((this.options.error != null) && typeof this.options.error === 'function') {
            //         this.options.error.call(this, response.meta.error_message);
            //         return false;
            //     } else {
            //         throw new Error("Error from Instagram: " + response.meta.error_message);
            //     }
            // }

            if (response.data.length === 0) {
                if ((this.options.error != null) && typeof this.options.error === 'function') {
                    this.options.error.call(this, 'No images were returned from Instagram');
                    return false;
                } else {
                    throw new Error('No images were returned from Instagram');
                }
            }
            if ((this.options.success != null) && typeof this.options.success === 'function') {
                this.options.success.call(this, response);
            }
            this.context.nextUrl = '';
            if (response.pagination != null) {
                this.context.nextUrl = response.pagination.next_url;
            }

            if (this.options.sortBy !== 'none') {
                if (this.options.sortBy === 'random') {
                    sortSettings = ['', 'random'];
                } else {
                    sortSettings = this.options.sortBy.split('-');
                }
                reverse = sortSettings[0] === 'least' ? true : false;
                switch (sortSettings[1]) {
                    case 'random':
                        response.data.sort(function () {
                            return 0.5 - Math.random();
                        });
                        break;
                    case 'recent':
                        response.data = this._sortBy(response.data, 'created_time', reverse);
                        break;
                    case 'liked':
                        response.data = this._sortBy(response.data, 'likes.count', reverse);
                        break;
                    case 'commented':
                        response.data = this._sortBy(response.data, 'comments.count', reverse);
                        break;
                    default:
                        throw new Error("Invalid option for sortBy: '" + this.options.sortBy + "'.");
                }
            }
            if ((typeof document !== "undefined" && document !== null) && this.options.mock === false) {
                images = response.data;
                parsedLimit = parseInt(this.options.limit, 10);

                if ((this.options.limit != null) && images.length > parsedLimit) {
                    images = images.slice(0, parsedLimit);
                }
                fragment = document.createDocumentFragment();
                if ((this.options.filter != null) && typeof this.options.filter === 'function') {
                    images = this._filter(images, this.options.filter);
                }
                //Template will be set in _makeTemplate
                // if ((this.options.template != null) && typeof this.options.template === 'string') {
                htmlString = '';
                imageString = '';
                videoURL = '';
                tmpEl = document.createElement('div');

                for (i = 0, len = images.length; i < len; i++) {
                    image = images[i];

                    imgOrient = "square";

                    imageUrl = "new" === this.options.api ? image.media_url : image.images.standard_resolution.url;

                    if ("VIDEO" === image.media_type) {
                        if (this.options.videos)
                            videoURL = image.media_url;
                        imageUrl = image.thumbnail_url;
                    }


                    httpProtocol = window.location.protocol.indexOf("http") >= 0;
                    if (httpProtocol && !this.options.useHttp) {
                        imageUrl = imageUrl.replace(/https?:\/\//, '//');
                    }
                    var j = [!0];

                    image.tags = this._extractTags(image.caption);

                    "" !== this.options.tagName && this.options.tagName.forEach(function (t, e) {
                        t = t.toLowerCase();
                        -1 === image.tags.indexOf(t) ? j[e] = !1 : j[e] = !0
                    });

                    var captionContent = image.caption;

                    if (image.caption && this.options.words !== "") {
                        captionContent = image.caption.split(/\s+/).slice(0, this.options.words).join(" ");
                        captionContent += "...";
                    }

                    if (-1 !== j.indexOf(true)) {
                        imageString = this._makeTemplate({
                            model: image,
                            id: image.id,
                            link: "old" === this.options.api ? image.link : image.permalink,
                            type: image.media_type,
                            image: imageUrl,
                            video: videoURL,
                            orientation: imgOrient,
                            caption: captionContent,
                            likes: "old" === this.options.api ? image.likes.count : false,
                            comments: "old" === this.options.api ? image.comments.count : false,
                            location: this._getObjectProperty(image, 'location.name')
                        });
                        htmlString += imageString;
                    }
                }
                tmpEl.innerHTML = htmlString;
                childNodesArr = [];
                childNodeIndex = 0;
                childNodeCount = tmpEl.childNodes.length;
                while (childNodeIndex < childNodeCount) {
                    childNodesArr.push(tmpEl.childNodes[childNodeIndex]);
                    childNodeIndex += 1;
                }
                this.options.afterLoad();
                for (j = 0, len1 = childNodesArr.length; j < len1; j++) {
                    node = childNodesArr[j];
                    fragment.appendChild(node);
                }
                //Template will be set in _makeTemplate
                // }
                targetEl = this.options.target;
                if (typeof targetEl === 'string') {
                    targetEl = document.getElementById(targetEl);
                }
                if (targetEl == null) {
                    eMsg = "No element with id=\"" + this.options.target + "\" on page.";
                    throw new Error(eMsg);
                }
                targetEl.appendChild(fragment);
                header = document.getElementsByTagName('head')[0];
                header.removeChild(document.getElementById('instafeed-fetcher'));
                instanceName = "instafeedCache" + this.unique;
                window[instanceName] = void 0;
                try {
                    delete window[instanceName];
                } catch (_error) {
                    e = _error;
                }
            }
            if ((this.options.after != null) && typeof this.options.after === 'function') {
                this.options.after.call(this);
            }
            return true;
        };

        Instafeed.prototype._buildUrl = function () {
            var base, endpoint, final;
            base = "new" === this.options.api ? "https://graph.instagram.com" : "https://api.instagram.com/v1";
            switch (this.options.get) {
                case "popular":
                    endpoint = "media/popular";
                    break;
                case "user":
                    endpoint = "new" === this.options.api ? "me/media?fields=id,media_type,media_url,username,timestamp,permalink,caption,children,thumbnail_url&limit=200" : "users/self/media/recent";
                    break;
                default:
                    throw new Error("Invalid option for get: '" + this.options.get + "'.");
            }

            final = base + "/" + endpoint;
            if (this.options.accessToken != null) {
                final += "new" === this.options.api ? "&" : "?";
                final += "access_token=" + this.options.accessToken;
            }
            // if (this.options.limit != null) {
            //     final += "&count=" + this.options.limit;
            // }

            final += "&callback=instafeedCache" + this.unique + ".parse";

            return final;
        };

        Instafeed.prototype._extractTags = function extractTags(str) {
            var exp = /#([^\s]+)/gi;
            var badChars = /[~`!@#$%^&*\(\)\-\+={}\[\]:;"'<>\?,\./|\\\s]+/i; // non-allowed characters
            var tags = [];

            if (typeof str === 'string') {
                while ((match = exp.exec(str)) !== null) {
                    if (badChars.test(match[1]) === false) {
                        tags.push(match[1].toLowerCase());
                    }
                }
            }

            return tags;
        };

        Instafeed.prototype._genKey = function () {
            var S4;
            S4 = function () {
                return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
            };
            return "" + (S4()) + (S4()) + (S4()) + (S4());
        };

        Instafeed.prototype._makeTemplate = function (data) {
            var output, pattern, ref, varName, varValue;
            pattern = /(?:\{{2})([\w\[\]\.]+)(?:\}{2})/;

            var templateData = this.options.templateData,
                videoSelector = "VIDEO" === data.type ? "premium-insta-video-wrap" : "";

            output =
                '<div class="premium-insta-feed ' + videoSelector + ' premium-insta-box"><div class="premium-insta-feed-inner"><div class="premium-insta-feed-wrap"><div class="premium-insta-img-wrap"><img src="{{image}}"/>';

            if ("VIDEO" === data.type && this.options.videos)
                output += '<video src={{video}} controls>';

            output += '</div><div class="premium-insta-info-wrap"><div class="premium-insta-feed-interactions">' +
                templateData.likes +
                templateData.comments +
                templateData.description +
                "</div></div>" +
                templateData.link +
                "</div>" + templateData.share + "</div></div>";

            while (pattern.test(output)) {

                varName = output.match(pattern)[1];

                varValue = (ref = this._getObjectProperty(data, varName)) != null ? ref : '';
                output = output.replace(pattern, function () {
                    return "" + varValue;
                });
            }
            return output;
        };

        Instafeed.prototype._getObjectProperty = function (object, property) {
            var piece, pieces;
            property = property.replace(/\[(\w+)\]/g, '.$1');
            pieces = property.split('.');
            while (pieces.length) {
                piece = pieces.shift();
                if ((object != null) && piece in object) {
                    object = object[piece];
                } else {
                    return null;
                }
            }
            return object;
        };

        Instafeed.prototype._sortBy = function (data, property, reverse) {
            var sorter;
            sorter = function (a, b) {
                var valueA, valueB;
                valueA = this._getObjectProperty(a, property);
                valueB = this._getObjectProperty(b, property);
                if (reverse) {
                    if (valueA > valueB) {
                        return 1;
                    } else {
                        return -1;
                    }
                }
                if (valueA < valueB) {
                    return 1;
                } else {
                    return -1;
                }
            };
            data.sort(sorter.bind(this));
            return data;
        };

        Instafeed.prototype._filter = function (images, filter) {
            var filteredImages, fn, i, image, len;
            filteredImages = [];
            fn = function (image) {
                if (filter(image)) {
                    return filteredImages.push(image);
                }
            };
            for (i = 0, len = images.length; i < len; i++) {
                image = images[i];
                fn(image);
            }
            return filteredImages;
        };

        return Instafeed;

    })();

    (function (root, factory) {
        if (typeof define === 'function' && define.amd) {
            return define([], factory);
        } else if (typeof module === 'object' && module.exports) {
            return module.exports = factory();
        } else {
            return root.Instafeed = factory();
        }
    })(this, function () {
        return Instafeed;
    });

}).call(this);