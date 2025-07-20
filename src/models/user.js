import { DataService } from "./data_service.js";
import { Settings } from "./settings.js";

/**
 * @class User
 * @memberof QuizApp
 * @version 1.0
 * @since 1.0: 
 *      - Created
 */
export class User {
    /**
     * @constructor
     */
    constructor(data_service){
        this.dataService = data_service;
    }
    /**
     * Load from data_service
     * @async
     * @param {void}
     * @returns {void}
     */
    async load(){
        this.data = await this.dataService.load();
    }
    /**
     * Save data via dataService from settings.data
     * @param {void}
     * @returns {void}
     */
    save(){
        this.dataService.save(this.data);
    }
}