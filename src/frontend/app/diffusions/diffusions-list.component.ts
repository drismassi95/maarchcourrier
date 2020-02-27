import { Component, EventEmitter, Input, OnInit, Output, Renderer2 } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { CdkDragDrop, transferArrayItem } from '@angular/cdk/drag-drop';
import { FormControl } from '@angular/forms';
import { catchError, map, tap, elementAt } from 'rxjs/operators';
import { of } from 'rxjs';
import { AlertComponent } from '../../plugins/modal/alert.component';
import { MatDialog } from '@angular/material';
import { FunctionsService } from '../../service/functions.service';

@Component({
    selector: 'app-diffusions-list',
    templateUrl: 'diffusions-list.component.html',
    styleUrls: ['diffusions-list.component.scss'],
})
export class DiffusionsListComponent implements OnInit {

    lang: any = LANG;
    roles: any = [];
    loading: boolean = true;
    availableRoles: any[] = [];
    keepRoles: any[] = [];
    currentEntityId: number = 0;
    userDestList: any[] = [];

    diffList: any = null;

    listinstanceClone: any = [];

    hasNoDest: boolean = false;

    /**
     * Ressource identifier to load listinstance (Incompatible with templateId)
     */
    @Input('resId') resId: number = null;

    /**
     * Add previous dest in copy (Only compatible with resId)
     */
    @Input('keepDestForRedirection') keepDestForRedirection: boolean = false;

    /**
     * Entity identifier to load listModel of entity (Incompatible with resId)
     */
    @Input('entityId') entityId: any = null;

    /**
     * For manage current loaded list
     */
    @Input('adminMode') adminMode: boolean = false;

    /**
     * Ids of related allowed entities perimeters
     */
    @Input('allowedEntities') allowedEntities: number[];

    /**
     * Expand all roles
     */
    @Input('expanded') expanded: boolean = false;

    /**
     * To load privilege of current list management
     * @param indexation
     * @param details
     * @param redirect
     */
    @Input('target') target: string = '';

    /**
     * FormControl to use this component in form
     */
    @Input('diffFormControl') diffFormControl: FormControl;

    /**
     * Catch external event after select an element in autocomplete
     */
    @Output('triggerEvent') triggerEvent = new EventEmitter();

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private renderer: Renderer2,
        public dialog: MatDialog,
        public functions: FunctionsService
    ) { }

    async ngOnInit(): Promise<void> {

        await this.initRoles();

        if (this.resId !== null && this.target !== 'redirect') {
            this.loadListinstance(this.resId);

        }
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            //moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else if (event.container.id != 'dest') {
            transferArrayItem(event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex);
        }
    }

    noReturnPredicate() {
        return false;
    }

    allPredicate() {
        return true;
    }

    async loadListModel(entityId: number) {
        this.loading = true;
        this.currentEntityId = entityId;
        this.userDestList = [];

        const listTemplates: any = await this.getListModel(entityId);
        this.removeAllItems();

        if (listTemplates.length > 0) {
            listTemplates[0].items.forEach((element: any) => {
                element.difflist_type = 'entity_id';
                if (element.item_mode == 'cc') {
                    this.diffList['copy'].items.push(element);
                } else {
                    this.diffList[element.item_mode].items.push(element);
                }
            });
        }
        if (this.resId !== null) {
            const listInstance: any = await this.getListinstance(this.resId);
            
            if (listInstance !== undefined) {
                listInstance.forEach((element: any) => {
                    if (element.item_mode == 'cc') {
                        element.item_mode = 'copy';
                    }
                    if (this.keepRoles.indexOf(element.item_mode) > -1 && this.diffList[element.item_mode].items.map((e: any) => { return e.item_id; }).indexOf(element.item_id) == -1) {
                        this.diffList[element.item_mode].items.push(element);
                    }
                    if (this.keepDestForRedirection && element.item_mode == "dest" && this.diffList["copy"].items.map((e: any) => { return e.item_id; }).indexOf(element.item_id) == -1) {
                        this.diffList["copy"].items.push(element);
                    }
                });
            }
        }

        if (this.diffFormControl !== undefined) {
            this.setFormValues();
        }

        this.listinstanceClone = JSON.parse(JSON.stringify(this.getCurrentListinstance()));
        this.loading = false;
    }

    getListModel(entityId: number) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/listTemplates/entities/${entityId}?type=diffusionList`).pipe(
                tap((data: any) => {

                    resolve(data.listTemplates);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getListinstance(resId: number) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/resources/${resId}/listInstance`).pipe(
                tap((data: any) => {
                    resolve(data.listInstance);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async loadListinstance(resId: number) {
        this.loading = true;

        const diffusions: any = await this.getListinstance(resId);
        this.removeAllItems();
        diffusions.forEach((element: any) => {
            if (element.item_mode == 'cc') {
                this.diffList['copy'].items.push(element);
            } else {
                this.diffList[element.item_mode].items.push(element);
            }
        });

        if (diffusions.filter((elem: any) => elem.item_mode === 'dest').length === 0 && this.adminMode) {
            this.adminMode = false;
            this.hasNoDest = true;
        }

        if (this.diffFormControl !== undefined) {
            this.setFormValues();
        }
        this.loading = false;
        this.listinstanceClone = JSON.parse(JSON.stringify(this.getCurrentListinstance()));
    }

    saveListinstance() {
        return new Promise((resolve, reject) => {
            const listInstance: any[] = [
                {
                    resId: this.resId,
                    listInstances: this.getCurrentListinstance()
                }
            ];
            this.http.put('../../rest/listinstances', listInstance).pipe(
                tap((data: any) => {
                    if (data && data.errors != null) {
                        this.notify.error(data.errors);
                    } else {
                        this.listinstanceClone = JSON.parse(JSON.stringify(this.getCurrentListinstance()));
                        this.notify.success(this.lang.diffusionListUpdated);
                        resolve(true);
                    }
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    initRoles() {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/roles?context=${this.target}`).pipe(
                map((data: any) => {
                    data.roles = data.roles.map((role: any) => {
                        return {
                            ...role,
                            id: role.id === 'cc' ? 'copy' : role.id,
                        }
                    });
                    return data.roles;
                }),
                tap((roles: any) => {
                    this.diffList = {};
                    this.availableRoles = roles;
                    this.availableRoles.forEach(element => {
                        this.diffList[element.id] = {
                            'label': element.label,
                            'items': []
                        };
                        if (element.keepInListInstance) {
                            this.keepRoles.push(element.id);
                        }
                    });
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    deleteItem(roleId: string, index: number) {
        this.diffList[roleId].items.splice(index, 1);
        if (this.diffFormControl !== undefined) {
            this.setFormValues();
        }
    }

    getCurrentListinstance() {
        let listInstanceFormatted: any = [];

        Object.keys(this.diffList).forEach(role => {
            if (this.diffList[role].items.length > 0) {
                this.diffList[role].items.forEach((element: any) => {
                    listInstanceFormatted.push({
                        difflist_type: element.difflist_type !== undefined ? element.difflist_type : element.object_type,
                        item_id: element.item_id,
                        item_mode: role == 'copy' ? 'cc' : role,
                        item_type: element.item_type,
                        process_date: element.process_date !== undefined ? element.process_date : null,
                        process_comment: element.process_comment,
                    });
                });
            }
        });

        return listInstanceFormatted;
    }

    loadDestUserList() {
        if (this.currentEntityId > 0 && this.userDestList.length == 0) {
            this.http.get("../../rest/entities/" + this.currentEntityId + "/users")
                .subscribe((data: any) => {
                    this.userDestList = data.users;
                    this.loading = false;
                }, (err: any) => {
                    this.notify.handleErrors(err);
                });
        }
    }

    changeDest(user: any) {
        this.diffList['dest'].items[0] = {
            difflist_type: "entity_id",
            item_type: "user_id",
            item_id: user.user_id,
            labelToDisplay: user.labelToDisplay,
            descriptionToDisplay: user.descriptionToDisplay,
            item_mode: "dest"
        };
    }

    getDestUser() {
        if (this.diffList !== null && this.diffList['dest']) {
            return this.diffList['dest'].items;
        } else {
            return false;
        }
    }

    async addElem(element: any) {
        let item_mode: any = 'copy';

        if (this.diffList["dest"].items.length === 0) {
            item_mode = await this.isUserInCurrentEntity(element.serialId) ? 'dest' : 'copy';
        }

        if (this.diffList["copy"].items.map((e: any) => { return e.item_id; }).indexOf(element.id) == -1) {
            let itemType = '';
            if (element.type == 'user') {
                itemType = 'user';
            } else {
                itemType = 'entity';
            }

            const newElemListModel = {
                userId: element.serialId,
                difflist_type: "entity_id",
                item_type: itemType,
                item_id: element.serialId,
                labelToDisplay: element.idToDisplay,
                descriptionToDisplay: element.descriptionToDisplay,
                item_mode: item_mode
            };
            this.diffList[item_mode].items.unshift(newElemListModel);

            if (this.diffFormControl !== undefined) {
                this.setFormValues();
            }
        }
    }

    isUserInCurrentEntity(userId: number) {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/entities/${this.currentEntityId}/users`).pipe(
                tap((data: any) => {
                    const state = data.users.filter((user: any) => user.id === userId).length > 0;
                    resolve(state);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    removeAllItems() {
        Object.keys(this.diffList).forEach((element: any) => {
            this.diffList[element].items = [];
        });
    }

    hasEmptyDest() {
        return this.diffList["dest"].items.length === 0;
    }

    isEmptyList() {
        let state = true;
        if (this.diffList !== null) {
            Object.keys(this.diffList).forEach((element: any) => {
                if (this.diffList[element].items.length > 0) {
                    state = false;
                }
            });
        }
        return state;
    }

    changeRole(user: any, oldRole: any, newRole: any) {
        if (newRole.id === 'dest') {
            this.switchUserWithOldDest(user, oldRole);

        } else {
            this.changeUserRole(user, oldRole, newRole);
        }
    }

    switchMode() {
        this.adminMode = !this.adminMode;

        if (this.adminMode && this.canUpdateRoles()) {
            setTimeout(() => {
                this.renderer.selectRootElement('#autoCompleteInput').focus();
            }, 100);

        }
    }

    switchUserWithOldDest(user: any, oldRole: any) {
        this.http.get("../../rest/users/" + user.userId + "/entities").pipe(
            map((data: any) => {
                data.entities = data.entities.map((entity: any) => entity.id);
                return data;
            }),
            tap((data: any) => {
                let indexFound = -1;
                let isAllowed: boolean = false;
                let allowedEntitiesIds: number[] = [];

                this.allowedEntities.forEach(allowedEntity => {
                    if (data.entities.indexOf(allowedEntity) > -1) {
                        isAllowed = true;
                        allowedEntitiesIds.push(data.entities[data.entities.indexOf(allowedEntity)]);
                    }
                });
                if (isAllowed) {
                    if (this.diffList['dest'].items.length > 0) {
                        const destUser = this.diffList['dest'].items[0];
                        indexFound = this.diffList[oldRole.id].items.map((item: any) => item.id).indexOf(destUser.id);

                        if (indexFound === -1) {
                            destUser.item_mode = oldRole.id;
                            this.diffList[oldRole.id].items.push(destUser);
                        }
                    }
                    indexFound = this.diffList[oldRole.id].items.map((item: any) => item.id).indexOf(user.id);

                    if (indexFound > -1) {
                        this.diffList[oldRole.id].items.splice(indexFound, 1);
                    }
                    user.item_mode = 'dest';
                    this.diffList['dest'].items[0] = user;

                    if (this.diffFormControl !== undefined) {
                        this.setFormValues();
                    }
                    // TO CHANGE DESTINATION IN INDEXING FORM
                    if (this.triggerEvent !== undefined) {
                        this.triggerEvent.emit(allowedEntitiesIds);
                    }
                } else {
                    this.dialog.open(AlertComponent, { autoFocus: false, disableClose: true, data: { title: this.lang.userUnauthorized, msg: "<b>" + user.labelToDisplay + "</b> " + this.lang.notInAuthorizedEntities } });
                }
            }),
        ).subscribe();
    }

    changeUserRole(user: any, oldRole: any, newRole: any) {
        let indexFound: number;

        indexFound = this.diffList[oldRole.id].items.map((item: any) => item.id).indexOf(user.id);

        if (indexFound > -1) {
            this.diffList[oldRole.id].items.splice(indexFound, 1);
        }
        user.item_mode = newRole.id;
        this.diffList[newRole.id].items.push(user);
        if (this.diffFormControl !== undefined) {
            this.setFormValues();
        }
    }

    setFormValues() {
        let arrValues: any[] = [];
        Object.keys(this.diffList).forEach(role => {
            arrValues = arrValues.concat(
                this.diffList[role].items.map((item: any) => {
                    return {
                        id: item.item_id,
                        mode: role,
                        type: item.item_type === 'user' ? 'user' : 'entity'
                    }
                })
            );
        });
        this.diffFormControl.setValue(arrValues);
        this.diffFormControl.markAsTouched();
    }

    canUpdateRoles() {
        return this.availableRoles.filter((role: any) => role.canUpdate === true).length > 0;
    }

    isModified() {
        return JSON.stringify(this.listinstanceClone) !== JSON.stringify(this.getCurrentListinstance());
    }
}
