import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Validators, FormGroup, FormBuilder } from '@angular/forms';
import { tap, catchError, finalize } from 'rxjs/operators';
import { AuthService } from '../../service/auth.service';
import { NotificationService } from '../notification.service';
import { environment } from '../../environments/environment';
import { LangService } from '../../service/app-lang.service';
import { of } from 'rxjs/internal/observable/of';
import { HeaderService } from '../../service/header.service';
import { FunctionsService } from '../../service/functions.service';
import { TimeLimitPipe } from '../../plugins/timeLimit.pipe';

@Component({
    templateUrl: 'login.component.html',
    styleUrls: ['login.component.scss'],
    providers: [TimeLimitPipe]
})
export class LoginComponent implements OnInit {
    lang: any = this.langService.getLang();
    loginForm: FormGroup;

    loading: boolean = false;
    showForm: boolean = false;
    environment: any;
    applicationName: string = '';
    loginMessage: string = '';

    constructor(
        private langService: LangService,
        private http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        public authService: AuthService,
        private functionsService: FunctionsService,
        private notify: NotificationService,
        public dialog: MatDialog,
        private formBuilder: FormBuilder,
        private timeLimit: TimeLimitPipe
    ) { }

    ngOnInit(): void {
        this.headerService.hideSideBar = true;
        this.loginForm = this.formBuilder.group({
            login: [null, Validators.required],
            password: [null, Validators.required]
        });

        this.environment = environment;
        if (this.authService.isAuth()) {
            if (!this.functionsService.empty(this.authService.getUrl(JSON.parse(atob(this.authService.getToken().split('.')[1])).user.id))) {
                this.router.navigate([this.authService.getUrl(JSON.parse(atob(this.authService.getToken().split('.')[1])).user.id)]);
            } else {
                this.router.navigate(['/home']);
            }
        } else {
            this.getLoginInformations();
        }
    }

    onSubmit() {
        this.loading = true;
        this.http.post(
            '../rest/authenticate',
            {
                'login': this.loginForm.get('login').value,
                'password': this.loginForm.get('password').value
            },
            {
                observe: 'response'
            }
        ).pipe(
            tap((data: any) => {
                this.authService.saveTokens(data.headers.get('Token'), data.headers.get('Refresh-Token'));
                this.authService.setUser({});
                if (!this.functionsService.empty(this.authService.getUrl(JSON.parse(atob(data.headers.get('Token').split('.')[1])).user.id))) {
                    this.router.navigate([this.authService.getUrl(JSON.parse(atob(data.headers.get('Token').split('.')[1])).user.id)]);
                } else {
                    this.router.navigate(['/home']);
                }
            }),
            catchError((err: any) => {
                this.loading = false;
                if (err.error.errors === 'Authentication Failed') {
                    this.notify.error(this.lang.wrongLoginPassword);
                } else if (err.error.errors === 'Account Suspended') {
                    this.notify.error(this.lang.accountSuspended);
                } else if (err.error.errors === 'Account Locked') {
                    this.notify.error(this.lang.accountLocked + ' ' + this.timeLimit.transform(err.error.date));
                } else {
                    this.notify.handleSoftErrors(err);
                }
                return of(false);
            })
        ).subscribe();
    }

    getLoginInformations() {
        this.http.get('../rest/authenticationInformations').pipe(
            tap((data: any) => {
                this.applicationName = data.applicationName;
                this.loginMessage = data.loginMessage;
            }),
            finalize(() => this.showForm = true),
            catchError((err: any) => {
                // TO DO : CONVERT custom.xml to json
                /*if (err.error.exception[0].message === 'Argument driver is empty') {
                    const configs = [{
                        custom_id : 'cs_recette',
                        ip : '',
                        external_domain : '',
                        path: 'cs_recette'
                    }];
                    const firstConfig = configs.map(item => item.custom_id).filter((customId) => !this.functionsService.empty(customId));

                    if (firstConfig.length > 0) {
                        const url = document.URL;
                        const splitUrl = url.split('/dist/');
                        window.location.href = `${splitUrl[0]}/${firstConfig[0]}/dist/${splitUrl[1]}`;
                    } else {
                        this.notify.error('Aucun custom ou fichier de configuration trouvé!');
                    }
                } else {
                    this.notify.handleSoftErrors(err);
                }*/
                return of(false);
            })
        ).subscribe();
    }
}
