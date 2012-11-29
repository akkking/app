/**
 * @test-framework QUnit
 * @test-require-asset extensions/wikia/AdEngine/js/WikiaDartHelper.js
 */

module('WikiaDartHelper');

test('getUrl Simple call', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgDBname: 'dbname',
			wgContentLanguage: 'xx'
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl default DB name', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgContentLanguage: 'xx'
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.vertical/_wikia/article;' +
			's0=vertical;s1=_wikia;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl page type', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgContentLanguage: 'xx',
			wikiaPageType: 'pagetype'
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.vertical/_wikia/pagetype;' +
			's0=vertical;s1=_wikia;s2=pagetype;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl Geo discovery', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgDBname: 'dbname',
			wgContentLanguage: 'xx'
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		geoMock = {},
		dartHelper,
		actual,
		expected,
		params = {
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			tile: 3
		};

	geoMock.getContinentCode = function() {return 'NA'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'North America -> ad');

	geoMock.getContinentCode = function() {return 'SA'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'South America -> ad');

	geoMock.getContinentCode = function() {return 'XX'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'Unknown -> ad');

	geoMock.getContinentCode = function() {return 'AF'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad-emea.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'Africa -> ad-emea');

	geoMock.getContinentCode = function() {return 'OC'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad-apac.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'Oceania -> ad-apac');

	geoMock.getContinentCode = function() {return 'EU'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad-emea.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'Europe -> ad-emea');

	geoMock.getContinentCode = function() {return 'AS'};
	geoMock.getCountryCode = function() {return 'QA'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad-emea.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'Qatar -> ad-emea');

	geoMock.getContinentCode = function() {return 'AS'};
	geoMock.getCountryCode = function() {return 'CN'};
	dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, geoMock, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl(params),
		expected = 'http://ad-apac.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';
	equal(actual, expected, 'China -> ad-apac');
});

test('getUrl Hub page: video games', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'www.wikia.com'},
			cityShort: 'wikia',
			cscoreCat: 'Gaming',
			wgDBname: 'wikiaglobal',
			wgContentLanguage: 'en',
			wgWikiaHubType: 'gaming',
			wikiaPageIsHub: true
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.hub/_gaming_hub/hub;' +
			's0=hub;s1=_gaming_hub;s2=hub;dmn=wikiacom;hostpre=www;' +
			'pos=SLOT_NAME;lang=en;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl Hub page: entertainment', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'www.wikia.com'},
			cityShort: 'wikia',
			cscoreCat: 'Entertainment',
			wgDBname: 'wikiaglobal',
			wgContentLanguage: 'en',
			wgWikiaHubType: 'ent',
			wikiaPageIsHub: true
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.hub/_ent_hub/hub;' +
			's0=hub;s1=_ent_hub;s2=hub;dmn=wikiacom;hostpre=www;' +
			'pos=SLOT_NAME;lang=en;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl Hub page: lifestyle', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'www.wikia.com'},
			cityShort: 'wikia',
			cscoreCat: 'Lifestyle',
			wgDBname: 'dbname',
			wgContentLanguage: 'en',
			wgWikiaHubType: 'life',
			wikiaPageIsHub: true
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.hub/_life_hub/hub;' +
			's0=hub;s1=_life_hub;s2=hub;dmn=wikiacom;hostpre=www;' +
			'pos=SLOT_NAME;lang=en;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl Hub page: bogus one', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'www.wikia.com'},
			cityShort: 'wikia',
			cscoreCat: 'Wikia',
			wgDBname: 'dbname',
			wgContentLanguage: 'en',
			wgWikiaHubType: 'life',
			wikiaPageIsHub: true
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.hub/_life_hub/hub;' +
			's0=hub;s1=_life_hub;s2=hub;dmn=wikiacom;hostpre=www;' +
			'pos=SLOT_NAME;lang=en;dis=large;hasp=yes;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getUrl Auto tile, same ord', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgDBname: 'dbname',
			wgContentLanguage: 'xx'
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		params = {
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub'
		},
		anotherParams = {
			slotsize: '200x300',
			slotname: 'ANOTHER_SLOT',
			subdomain: 'sub'
		},
		yetAnotherParams = {
			slotsize: '200x400',
			slotname: 'YET_ANOTHER_SLOT',
			subdomain: 'sub'
		},
		actual,
		actualOrd,
		expected;

	actual = dartHelper.getUrl(params);
	actualOrd = actual.match(/;ord=([0-9]*)\?/)[1];

	expected = 'http://sub.doubleclick.net/adj/wka.vertical/_dbname/article;' +
		's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
		'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;src=driver;sz=100x200;' +
		'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=1;endtag=$;ord=' +
		actualOrd + '?';

	equal(actual, expected, 'tile 1');

	actual = dartHelper.getUrl(anotherParams);
	expected = 'http://sub.doubleclick.net/adj/wka.vertical/_dbname/article;' +
		's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
		'pos=ANOTHER_SLOT;lang=xx;dis=large;hasp=yes;src=driver;sz=200x300;' +
		'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=2;endtag=$;ord=' +
		actualOrd + '?';

	equal(actual, expected, 'tile 2');

	actual = dartHelper.getUrl(yetAnotherParams);
	expected = 'http://sub.doubleclick.net/adj/wka.vertical/_dbname/article;' +
		's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
		'pos=YET_ANOTHER_SLOT;lang=xx;dis=large;hasp=yes;src=driver;sz=200x400;' +
		'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=' +
		actualOrd + '?';

	equal(actual, expected, 'tile 3');
});

test('getUrl Page categories', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgDBname: 'dbname',
			wgContentLanguage: 'xx',
			wgCategories: ['Category', 'Another Category', 'YetAnother Category']
		},
		documentMock = {documentElement: {}, body: {clientWidth: 1300}},
		adLogicShortPageMock = {hasPreFooters: function() {return true;}},
		dartHelper = WikiaDartHelper(logMock, windowMock, documentMock, {}, {}, adLogicShortPageMock),
		actual = dartHelper.getUrl({
			ord: 7,
			slotsize: '100x200',
			slotname: 'SLOT_NAME',
			subdomain: 'sub',
			tile: 3
		}),
		expected = 'http://sub.doubleclick.net/adj/wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;' +
			'pos=SLOT_NAME;lang=xx;dis=large;hasp=yes;' +
			'cat=category;cat=another_category;cat=yetanother_category;src=driver;sz=100x200;' +
			'mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;tile=3;endtag=$;ord=7?';

	equal(actual, expected);
});

test('getMobileUrl', function() {
	var logMock = function() {},
		windowMock = {
			location: {hostname: 'example.org'},
			cityShort: 'vertical',
			wgDBname: 'dbname',
			wgContentLanguage: 'xx'
		},
		documentMock = {documentElement: {}, body: {}},
		dartHelper = WikiaDartMobileHelper(logMock, windowMock, documentMock),
		expected = 'http://ad.mo.doubleclick.net/DARTProxy/mobile.handler?k=wka.vertical/_dbname/article;' +
			's0=vertical;s1=_dbname;s2=article;dmn=exampleorg;hostpre=example;pos=SLOTNAME_MOBILE;' +
			'lang=xx;hasp=no;positionfixed=css;src=mobile;sz=5x5;mtfIFPath=/extensions/wikia/AdEngine/;mtfInline=true;' +
			'tile=1;ord=XXX?&csit=1&dw=1&u=someuniqid';

	equal(dartHelper.getMobileUrl({
		slotname: 'SLOTNAME_MOBILE',
		positionfixed: 'css',
		uniqueId: 'someuniqid'
	}).replace(/ord=[0-9]+/, 'ord=XXX'), expected);
});