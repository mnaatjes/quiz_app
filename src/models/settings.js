/**
 * @class Settings
 * @memberof QuizApp
 * @version 1.0
 * @since 1.0: 
 *      - Created
 */
export class Settings {
    /**
     * @constructor
     */
    constructor(data_service){
        this.dataService = data_service;
        this.data = {};
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