import { Fetch, Configuration } from './Fetch'
import { Identifier } from '../model/Type'
import { SensorSpec } from '../model/SensorSpec'

export class SensorSpecService {
    public configuration?: Configuration

    /**
     * Get all SensorSpecs registered by any Researcher.
     */
    public async all(): Promise<SensorSpec[]> {
        return (await Fetch.get<{data: any[]}>(`/sensor_spec`, this.configuration)).data.map(x => Object.assign(new SensorSpec(), x))
    }

    /**
     * Create a new SensorSpec.
     * @param sensorSpec 
     */
    public async create(sensorSpec: SensorSpec): Promise<Identifier> {
        if (sensorSpec === null || sensorSpec === undefined)
            throw new Error('Required parameter sensorSpec was null or undefined when calling sensorSpecCreate.')

        return (await Fetch.post(`/sensor_spec`, sensorSpec, this.configuration))
    }

    /**
     * Delete an SensorSpec.
     * @param sensorSpecName 
     */
    public async delete(sensorSpecName: string): Promise<Identifier> {
        if (sensorSpecName === null || sensorSpecName === undefined)
            throw new Error('Required parameter sensorSpecName was null or undefined when calling sensorSpecDelete.')

        return (await Fetch.delete(`/sensor_spec/${sensorSpecName}`, this.configuration))
    }

    /**
     * Update an SensorSpec.
     * @param sensorSpecName 
     * @param sensorSpec 
     */
    public async update(sensorSpecName: string, sensorSpec: SensorSpec): Promise<Identifier> {
        if (sensorSpecName === null || sensorSpecName === undefined)
            throw new Error('Required parameter sensorSpecName was null or undefined when calling sensorSpecUpdate.')
        if (sensorSpec === null || sensorSpec === undefined)
            throw new Error('Required parameter sensorSpec was null or undefined when calling sensorSpecUpdate.')

        return (await Fetch.put(`/sensor_spec/${sensorSpecName}`, sensorSpec, this.configuration))
    }

    /**
     * Get a SensorSpec.
     * @param sensorSpecName 
     */
    public async view(sensorSpecName: string): Promise<SensorSpec> {
        if (sensorSpecName === null || sensorSpecName === undefined)
            throw new Error('Required parameter sensorSpecName was null or undefined when calling sensorSpecView.')

        return (await Fetch.get<{data: any[]}>(`/sensor_spec/${sensorSpecName}`, this.configuration)).data.map(x => Object.assign(new SensorSpec(), x))[0]
    }
}