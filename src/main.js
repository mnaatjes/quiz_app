/**
 * @file main.js
 * @version 1.0
 * @since 1.0
 *  - Created
 */
import { DataService } from "./models/data_service.js";
import { Settings } from "./models/settings.js";
localStorage.clear();
const settings  = new Settings(new DataService("src/data/template_objects/settings.json", "settings"));
await settings.load();
settings.data.sound.enabled = false;
settings.save();
console.log(settings.data);