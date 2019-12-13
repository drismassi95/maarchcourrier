import { Component, OnInit, Input, ViewChild, ElementRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { AppService } from '../../../service/app.service';
import { SortPipe } from '../../../plugins/sorting.pipe';
import { FormControl } from '@angular/forms';
import { Observable, of } from 'rxjs';
import { debounceTime, filter, distinctUntilChanged, tap, switchMap, exhaustMap, catchError } from 'rxjs/operators';
import { LatinisePipe } from 'ngx-pipes';
import { PrivilegeService } from '../../../service/privileges.service';

@Component({
    selector: 'app-contact-autocomplete',
    templateUrl: "contact-autocomplete.component.html",
    styleUrls: [
        'contact-autocomplete.component.scss',
        '../../indexation/indexing-form/indexing-form.component.scss'
    ],
    providers: [NotificationService, AppService, SortPipe]
})

export class ContactAutocompleteComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = false;
    loadingValues: boolean = true;

    key: string = 'id';

    canAdd: boolean = false;

    listInfo: string;
    myControl = new FormControl();
    filteredOptions: Observable<string[]>;
    options: any;
    valuesToDisplay: any = {};
    dialogRef: MatDialogRef<any>;
    newIds: number[] = [];

    /**
     * FormControl used when autocomplete is used in form and must be catched in a form control.
     */
    @Input('control') controlAutocomplete: FormControl;

    @ViewChild('autoCompleteInput', { static: true }) autoCompleteInput: ElementRef;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private headerService: HeaderService,
        public appService: AppService,
        private latinisePipe: LatinisePipe,
        private privilegeService: PrivilegeService
    ) {

    }

    ngOnInit() {
        console.log(this.controlAutocomplete);
        this.controlAutocomplete.setValue(this.controlAutocomplete.value === null || this.controlAutocomplete.value === '' ? [] : this.controlAutocomplete.value);
        this.canAdd = this.privilegeService.hasCurrentUserPrivilege('manage_tags_application');
        this.initFormValue();
        this.initAutocompleteRoute();
    }

    initAutocompleteRoute() {
        this.listInfo = this.lang.autocompleteInfo;
        this.options = [];
        this.myControl.valueChanges
            .pipe(
                //tap((value) => this.canAdd = value.length === 0 ? false : true),
                debounceTime(300),
                filter(value => value.length > 2),
                distinctUntilChanged(),
                tap(() => this.loading = true),
                switchMap((data: any) => this.getDatas(data)),
                tap((data: any) => {
                    if (data.length === 0) {
                        this.listInfo = this.lang.noAvailableValue;
                    } else {
                        this.listInfo = '';
                    }
                    this.options = data;
                    this.filteredOptions = of(this.options);
                    this.loading = false;
                })
            ).subscribe();
    }

    getDatas(data: string) {
        return this.http.get('../../rest/autocomplete/all', { params: { "search": data } });
    }

    selectOpt(ev: any) {
        this.setFormValue(ev.option.value);
        this.myControl.setValue('');

    }

    initFormValue() {
        this.controlAutocomplete.value.forEach((contact: any) => {
            if (contact.type === 'contact') {
                this.http.get('../../rest/contacts/' + contact.id).pipe(
                    tap((data: any) => {
                        this.valuesToDisplay[data.id] = {
                            type: 'contact',
                            firstname: data.firstname,
                            lastname: data.lastname,
                            company: data.company
                        };
                        
                    })
                ).subscribe();
            } else if (contact.type === 'user') {
                this.http.get('../../rest/users/' + contact.id).pipe(
                    tap((data: any) => {
                        this.valuesToDisplay[data.id] = {
                            type: 'user',
                            lastname: data.entity_label,
                        };
                    })
                ).subscribe();
            } else if (contact.type === 'entity') {
                this.http.get('../../rest/entities/' + contact.id).pipe(
                    tap((data: any) => {
                        this.valuesToDisplay[data.id] = {
                            type: 'entity',
                            lastname: data.entity_label,
                        };
                    })
                ).subscribe();
            }
            
        });
        this.loadingValues = false;
    }

    setFormValue(item: any) {
        if (this.controlAutocomplete.value.map((contact: any) => contact.id).indexOf(item['id']) === -1) {
            let arrvalue = [];
            if (this.controlAutocomplete.value !== null) {
                arrvalue = this.controlAutocomplete.value;
            }
            arrvalue.push(
                {
                    type: item['type'],
                    id: item['id']

                });
            this.valuesToDisplay[item['id']] = item;
            this.controlAutocomplete.setValue(arrvalue);
            this.loadingValues = false;
        }
    }

    resetAutocomplete() {
        this.options = [];
        this.listInfo = this.lang.autocompleteInfo;
    }

    private _filter(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.options.filter((option: any) => this.latinisePipe.transform(option[this.key].toLowerCase()).includes(filterValue));
        } else {
            return this.options;
        }
    }

    unsetValue() {
        this.controlAutocomplete.setValue('');
        this.myControl.setValue('');
        this.myControl.enable();
    }

    removeItem(index: number) {

        if (this.newIds.indexOf(this.controlAutocomplete.value[index]) === -1) {
            let arrValue = this.controlAutocomplete.value;
            this.controlAutocomplete.value.splice(index, 1);
            this.controlAutocomplete.setValue(arrValue);
        } else {
            this.http.delete('../../rest/tags/' + this.controlAutocomplete.value[index]).pipe(
                tap((data: any) => {
                    let arrValue = this.controlAutocomplete.value;
                    this.controlAutocomplete.value.splice(index, 1);
                    this.controlAutocomplete.setValue(arrValue);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    addItem() {
        const newElem = {};

        newElem[this.key] = this.myControl.value;

        this.http.post('../../rest/tags', { label: newElem[this.key] }).pipe(
            tap((data: any) => {
                for (var key in data) {
                    newElem['id'] = data[key];
                    this.newIds.push(data[key]);
                }
                this.setFormValue(newElem);
                this.myControl.setValue('');
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    empty(value: any) {
        if (value !== null && value !== '' && value !== undefined) {
            return false;
        } else {
            return true;
        }
    }
}