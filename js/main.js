//--- Component classes---//

class CreatePostFormCmp extends UIComponent {
	form;
	apiUrl;

	constructor($viewContainer, dependencies) {
		super($viewContainer);
		this.setDependencies(dependencies);
	}

	setDependencies(dependencies) {
		if (!(dependencies?.form instanceof FormUtil)) {
			throw new TypeError('Missing dependency: form');
		}
		this.form = dependencies.form;

		if (!dependencies?.apiUrl) {
			throw new TypeError('Missing dependency: apiUrl');
		}
		this.apiUrl = dependencies.apiUrl;
	}

	init() {
		this.initForm();
	}

	initForm() {
		const params = {
			selectors: { form: 'form', submitBtn: '[type="submit"]' },
			onSubmit: ev => {
				ev.preventDefault();
				if (!this.form.disabled) this.submit();
			}
		}
		this.form.init(params);
	}

	submit() {
		const loadingName = 'form';
		if (this.isLoading(loadingName)) return;
		this.loading(loadingName);

		this.formDisable();

		const req = {
			method: 'create_post',
			params: {
				user: this.formGetUser(),
				post: this.formGetPost(),
			},
		};

		$.get(
			this.apiUrl,
			req,
			res => this.submitSuccess(res),
			'json'
		).fail(() => this.ajaxFail())
			.always(() => {
				this.loading(loadingName, true);
				this.formDisable(false);
			});
	}

	submitSuccess(res) {
		if (res.status != 'OK') {
			if (res.status == 'VALIDATION_FAIL') {
				// TODO:
				alert('Validation failed!');
				console.error(res.data.errors);
			} else {
				this.ajaxError(res);
			}
		} else {
			this.clearForm();
			alert(`
				Success!
				User id: ${res.data.user.id}.
				Post id: ${res.data.post.id}.
			`.replace(/\t/g, ''));
		}
	}

	formDisable(disable) {
		this.form.disable(disable);
	}

	formClear() {
		this.form.clear();
	}

	formGetUser() {
		return {
			name: this.form.getInput('name').val(),
			email: this.form.getInput('email').val(),
		};
	}

	formGetPost() {
		return {
			title: this.form.getInput('title').val(),
			body: this.form.getInput('body').val(),
		};
	}
}

class UserStatsCmp extends UIComponent {
	apiUrl;
	$reportRows;

	init() {
		this._$viewContainer.css('min-height', '20rem');
		this.$reportRows = this.find('[data-id="report-rows"]');
		this.getReport();
	}

	getReport() {
		const loadingName = 'report';
		if (this.isLoading(loadingName)) return;
		this.loading(loadingName);

		const req = {
			method: 'user_stats',
			params: {},
		};
		$.get(
			this.apiUrl,
			req,
			res => this.getReportSuccess(res),
			'json'
		).fail(() => this.ajaxFail())
			.always(() => {
				this.loading(loadingName, true);
			});
	}

	getReportSuccess(res) {
		if (res.status != 'OK') {
			this.ajaxError(res);
		} else {
			this.drawReportRows(res.data);
		}
	}

	drawReportRows(data) {
		this.$reportRows.empty();
		data.map(row => {
			const tr = `
				<tr>
					<td>${row.user_id}</td>
					<td>${row.monthly_average}</td>
					<td>${row.weekly_average}</td>
				</tr>
			`;
			this.$reportRows.append(tr);
		});
	}
}
//--- /Component classes---//


$(function () {

	const apiUrl = 'api/';

	const $body = $('body');
	UIComponent.initLoading($body);

	const componentsConfig = [
		{
			view: 'create-post-form',
			ctor: CreatePostFormCmp,
			dependencies: {
				form: new FormUtil(new ParamsUtil()),
				apiUrl,
			}
		},
		{
			view: 'user-stats',
			ctor: UserStatsCmp,
			dependencies: {
				apiUrl,
			}
		},
	];
	const cmpInstances = [];

	for (const cfg of componentsConfig) {
		$body.find(`[data-view="${cfg.view}"]`).each((i, el) => {
			const cmp = new cfg.ctor($(this), cfg.dependencies);
			cmp.init();
			cmpInstances.push(cmp);
		});
	}

	//--- posts ---//
	const $postsContent = $body.find('#postsContent');

	//json
	const $searchBy = $postsContent.find('[data-input="search-by"]');
	const $searchParam = $postsContent.find('[data-input="search-param"]');
	$postsContent.find('[data-action="search"]').click(() => {
		const url = `posts-json.php?${$searchBy.val()}=${$searchParam.val()}`;
		window.open(url, '_blank');
	});

	//fetch data
	$postsContent.find('[data-action="fetch-data"]').click(() => {
		UIComponent.loading($postsContent);
		fetchData(apiURL);
	});

	function fetchData(apiURL) {
		const req = {
			method: 'fetch_remote_data',
			params: {}
		};
		$.get(
			apiURL,
			req,
			res => fetchDataSuccess(res),
			'json'
		).fail(() => ajaxFail())
			.always(() => {
				UIComponent.loading($postsContent, true);
			});
	}

	function fetchDataSuccess(res) {
		if (res.status != 'OK') {
			ajaxError(res);
		} else {
			alert(`
				Success!
				Inserted users: ${res.data.inserted_users}.
				Inserted posts: ${res.data.inserted_posts}.
			`.replace(/\t/g, ''));
		}
	}
	//--- /posts ---//

});
