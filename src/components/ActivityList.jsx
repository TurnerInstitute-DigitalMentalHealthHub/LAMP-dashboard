
// Core Imports
import React, { useState, useEffect, useCallback } from 'react'
import { IconButton, Box, Icon, Button, Typography, Dialog, DialogActions, Slide } from '@material-ui/core'
import MaterialTable from 'material-table'
import { useSnackbar } from 'notistack'

// External Imports 
import { saveAs } from 'file-saver'
import { useDropzone } from 'react-dropzone'

// Local Imports
import LAMP from '../lamp'
import Activity from './Activity'
import SurveyCreator from './SurveyCreator'
import ResponsiveDialog from './ResponsiveDialog'


// TODO: Blogs/Tips/AppHelp


// Splice a raw Activity object with its ActivityDescription object.
export function spliceActivity({ raw, tag }) {
    return { 
        id: raw.id,
        spec: 'lamp.survey',
        name: raw.name,
        description: tag?.description,
        schedule: raw.schedule,
        settings: !Array.isArray(raw.settings) ? raw.settings : raw.settings.map((question, idx) => ({ 
            text: question.text, 
            type: tag?.questions?.[idx]?.multiselect === true ? 'multiselect' : question.type,
            description: tag?.questions?.[idx]?.description,
            options: question.options === null ? null : question.options?.map((z, idx2) => ({
                value: z,
                description: tag?.questions?.[idx]?.options?.[idx2]
            }))
        }))
    }
}

// Un-splice an object into its raw Activity object and ActivityDescription object.
export function unspliceActivity(x) {
    return { 
        raw: {
            id: x.id,
            spec: 'lamp.survey',
            name: x.name,
            schedule: x.schedule,
            settings: x.settings.map(y => ({
                text: y.text,
                type: y.type === 'multiselect' ? 'list' : y.type,
                options: y.options === null ? null : y.options?.map(z => z.value)
            }))
        }, 
        tag: {
            description: x.description,
            questions: x.settings.map(y => ({
                multiselect: y.type === 'multiselect' ? true : undefined,
                description: y.description,
                options: y.options === null ? null : y.options?.map(z => z.description)
            }))
        } 
    }
}

export default function ActivityList({ title, activities, studyID, onChange, ...props }) {
    const [showCreate, setShowCreate] = useState()
    const [showActivityImport, setShowActivityImport] = useState()
    const [importFile, setImportFile] = useState()
    const [selectedActivity, setSelectedActivity] = useState()
    const { enqueueSnackbar } = useSnackbar()
    useEffect(() => { onChange() }, [showCreate])
    const onDrop = useCallback(acceptedFiles => {
        const reader = new FileReader()
        reader.onabort = () => enqueueSnackbar('Couldn\'t import the Activities.', { variant: 'error' })
        reader.onerror = () => enqueueSnackbar('Couldn\'t import the Activities.', { variant: 'error' })
        reader.onload = () => {
            setShowActivityImport()
            let obj = JSON.parse(atob(reader.result))
            if (Array.isArray(obj) && obj.filter(x => (typeof x === 'object' && !!x.name && !!x.settings && !!x.schedule)).length > 0)
                setImportFile(obj)
            else enqueueSnackbar('Couldn\'t import the Activities.', { variant: 'error' })
        }
        acceptedFiles.forEach(file => reader.readAsText(file))
    }, [])
    // eslint-disable-next-line
    const { acceptedFiles, getRootProps, getInputProps, isDragActive, isDragAccept } = useDropzone({
        onDrop, accept: 'application/json,.json', maxSize: 1 * 1024 * 1024 /* 1MB */
    })
    const _saveFile = (data) => saveAs(new Blob([btoa(JSON.stringify(data))], { type: 'text/plain;charset=utf-8' }), 'export.json')



    // ---------------------------



    // Import a file containing pre-linked Activity objects from another Study.
    const importActivities = async () => {
        let allIDs = importFile.map(x => x.id).reduce((prev, curr) => ({ ...prev, [curr]: undefined }), {})
        let brokenGroupsCount = importFile
            .filter(activity => activity.spec === 'lamp.group')
            .filter(activity => activity.settings
                .filter(x => !Object.keys(allIDs).includes(x)).length > 0)
            .length
        if (brokenGroupsCount > 0) {
            setImportFile()
            enqueueSnackbar('Couldn\'t import the Activities because some Activities are misconfigured or missing.', { variant: 'error' })
            return
        }

        // Groups only.
        for (let x of importFile.filter(x => ['lamp.group'].includes(x.spec))) {
            try {
                await LAMP.Activity.create(studyID, { ...x, id: undefined, tableData: undefined, settings: x.settings.map(y => allIDs[y]) })
            } catch(e) {
                enqueueSnackbar("Couldn't import one of the selected Activity groups.", { variant: 'error' })
            }
        }

        // Surveys only.
        for (let x of importFile.filter(x => ['lamp.survey'].includes(x.spec))) {
            const { raw, tag } = unspliceActivity(x)
            try {
                allIDs[raw.id] = (await LAMP.Activity.create(studyID, { ...raw, id: undefined, tableData: undefined })).data
                await LAMP.Type.setAttachment(raw.id, 'me', 'lamp.dashboard.survey_description', tag)
            } catch(e) {
                enqueueSnackbar("Couldn't import one of the selected survey Activities.", { variant: 'error' })
            }
        }

        // CTests only.
        for (let x of importFile.filter(x => !['lamp.group', 'lamp.survey'].includes(x.spec))) {
            try {
                allIDs[x.id] = (await LAMP.Activity.create(studyID, { ...x, id: undefined, tableData: undefined })).data
            } catch(e) {
                enqueueSnackbar("Couldn't import one of the selected cognitive test Activities.", { variant: 'error' })
            }
        }

        onChange()
        setImportFile()
        enqueueSnackbar("The selected Activities were successfully imported.", { variant: 'info' })
    }

    // Export a file containing this Study's pre-linked Activity objects.
    const downloadActivities = async (rows) => {
        let data = []
        for (let x of rows) {
            if (x.spec === 'lamp.survey') {
                try {
                    let res = await LAMP.Type.getAttachment(x.id, 'lamp.dashboard.survey_description')
                    let activity = spliceActivity({ raw: { ...x, tableData: undefined }, tag: !!res.error ? undefined : res.data })
                    data.push(activity)
                } catch(e) {}
            } else data.push({ ...x, tableData: undefined })
        }
        _saveFile(data)
        enqueueSnackbar("The selected Activities were successfully exported.", { variant: 'info' })
    }

    // Create a new Activity object & survey descriptions if set.
    const saveActivity = async (x) => {
        // FIXME: ensure this is a lamp.survey only!
        const { raw, tag } = unspliceActivity(x)
        let newItem = await LAMP.Activity.create(studyID, raw)
        await LAMP.Type.setAttachment(newItem.data, 'me', 'lamp.dashboard.survey_description', tag)
        enqueueSnackbar("Successfully created a new Activity.", { variant: 'success' })
        setShowCreate()
    }

    // Delete the selected Activity objects & survey descriptions if set.
    const deleteActivities = async (rows) => {
        for (let activity of rows) {
            if (activity.spec === 'lamp.survey') {
                let tag = await LAMP.Type.setAttachment(activity.id, 'me', 'lamp.dashboard.survey_description', null)
                console.dir('deleted tag ' + JSON.stringify(tag))
            }
            let raw = await LAMP.Activity.delete(activity.id)
            console.dir(raw)
        }
        enqueueSnackbar("Successfully deleted the selected Activities.", { variant: 'success' })
        onChange()
    }

    // Begin an Activity object modification (ONLY DESCRIPTIONS).
    const modifyActivity = async (raw) => {
        if (raw.spec === 'lamp.survey') {
            let tag = [await LAMP.Type.getAttachment(raw.id, 'lamp.dashboard.survey_description')].map(y => !!y.error ? undefined : y.data)[0]
            const activity = spliceActivity({ raw, tag })
            setSelectedActivity(activity)
        } else setSelectedActivity(raw)
    }

    // Commit an update to an Activity object (ONLY DESCRIPTIONS).
    const updateActivity = async (x, isDuplicated) => {
        const { raw, tag } = unspliceActivity(x)
        if (isDuplicated) /* duplicate */ {

            let newItem = await LAMP.Activity.create(studyID, raw)
            await LAMP.Type.setAttachment(newItem.data, 'me', 'lamp.dashboard.survey_description', tag)
            enqueueSnackbar("Successfully duplicated the Activity under a new name.", { variant: 'success' })
            onChange()
        } else /* overwrite */ {

            /* // FIXME: DISABLED UNTIL FURTHER NOTICE!
            raw.id = selectedActivity.id
            raw.schedule = selectedActivity.schedule
            await LAMP.Activity.updateActivity(raw)
            */
            await LAMP.Type.setAttachment(selectedActivity.id, 'me', 'lamp.dashboard.survey_description', tag)
            enqueueSnackbar('Only survey description content was modified to prevent irrecoverable data loss.', { variant: 'error' })
        }
        setSelectedActivity()
    }

	return (
        <React.Fragment>
            <MaterialTable 
                title={title}
                data={activities} 
                columns={[
                    { title: 'Name', field: 'name' }, 
                    { title: 'Type', field: 'spec', lookup: { 'lamp.survey': 'Survey', 'lamp.group': 'Group' }, emptyValue: 'Cognitive Test' },
                ]}
                onRowClick={(event, rowData, togglePanel) => modifyActivity(rowData)}
                actions={[
                    {
                        icon: 'cloud_upload',
                        tooltip: 'Import',
                        isFreeAction: true,
                        onClick: (event, rows) => setShowActivityImport(true)
                    }, {
                        icon: 'cloud_download',
                        tooltip: 'Export',
                        onClick: (event, rows) => downloadActivities(rows)
                    }, {
                        icon: 'add_box',
                        tooltip: 'Create',
                        isFreeAction: true,
                        onClick: (event, rows) => setShowCreate(true)
                    }, {
                        icon: 'delete_forever',
                        tooltip: 'Delete',
                        onClick: async (event, rows) => deleteActivities(rows)
                    },
                ]}
                localization={{
                    body: {
                        emptyDataSourceMessage: 'No Activities. Add Activities by clicking the [+] button above.',
                        editRow: {
                            deleteText: 'Are you sure you want to delete this Activity?'
                        }
                    }
                }}
                options={{
                    selection: true,
                    actionsColumnIndex: -1,
                    pageSize: 10,
                    pageSizeOptions: [10, 25, 50, 100]
                }}
                components={{ Container: props => <div {...props} /> }}
            />
            <Dialog open={!!showActivityImport} onClose={() => setShowActivityImport()} >
                <Box {...getRootProps()} 
                    p={4} 
                    bgcolor={(isDragActive || isDragAccept) ? 'primary.main' : undefined} 
                    color={!(isDragActive || isDragAccept) ? 'primary.main' : '#fff'}
                >
                    <input {...getInputProps()} />
                    <Typography variant="h6">
                        Drag files here, or click to select files.
                    </Typography>
                </Box>
            </Dialog>
            <Dialog open={!!importFile} onClose={() => setImportFile()} >
                <MaterialTable 
                    title="Continue importing?"
                    data={importFile || []} 
                    columns={[{ title: 'Activity Name', field: 'name' }]}
                    options={{ search: false, selection: false }}
                    components={{ Container: props => <div {...props} /> }}
                />
                <DialogActions>
                    <Button onClick={() => setImportFile()} color="secondary" autoFocus>
                        Cancel
                    </Button>
                    <Button onClick={importActivities} color="primary" autoFocus>
                        Import
                    </Button>
                </DialogActions>
            </Dialog>
            <ResponsiveDialog fullScreen transient animate open={!!showCreate} onClose={() => setShowCreate()}>
                <Box py={8} px={4}>
                    <SurveyCreator onSave={saveActivity} />
                </Box>
            </ResponsiveDialog>
            <ResponsiveDialog fullScreen transient animate open={!!selectedActivity} onClose={() => setSelectedActivity()}>
                <Activity activity={selectedActivity} studyID={studyID} onSave={updateActivity} />
            </ResponsiveDialog>
        </React.Fragment>
    )
}