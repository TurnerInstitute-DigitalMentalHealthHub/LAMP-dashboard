
// Core Imports
import React, { useState, useEffect } from 'react'
import { 
  Box, Tooltip, Typography, Grid, Fab, Divider, 
  IconButton, Icon, Button, ButtonGroup, TextField, 
  InputAdornment, Stepper, Step, StepLabel, StepButton, 
  StepContent, Radio, RadioGroup, FormControlLabel,
  FormGroup, Checkbox, Menu, MenuItem
} from '@material-ui/core'

function ActivitySelector({ activities, selected, onSave, onDelete, ...props }) {
  const [_selected, setSelected] = useState(selected)
  const [anchorEl, setAnchorEl] = useState()
  useEffect(() => {
    if (_selected !== selected)
      onSave && onSave()
  }, [_selected])
  return (
    <React.Fragment>
      <ButtonGroup>
        <Button 
          variant="outlined" 
          color={_selected?.name ? 'primary' : 'secondary'} 
          onClick={e => setAnchorEl(e.currentTarget)}
        >
          {_selected?.name ?? 'No selection'}
        </Button>
        <Button 
          variant="outlined" 
          color={_selected?.name ? 'primary' : 'secondary'} 
          onClick={() => onDelete && onDelete()}
        >
          <Icon>delete_forever</Icon>
        </Button>
      </ButtonGroup>
      <Menu
        open={Boolean(anchorEl)}
        anchorEl={anchorEl}
        onClose={() => setAnchorEl()}
      >
        {activities.map(activity =>
          <MenuItem onClick={() => { setAnchorEl(); setSelected(activity) }}>{activity.name}</MenuItem>
        )}
      </Menu>
    </React.Fragment>
  )
}

export default function GroupCreator({ activities, value, onSave, ...props }) {
  const [activeStep, setActiveStep] = useState(0)
  const [text, setText] = useState(!!value ? value.name : undefined)
  const [questions, setQuestions] = useState(!!value ? value.settings : [])

	return (
        <Grid container direction="column" spacing={2}>
            <Grid item>
                <Typography variant="h4">{!!value ? 'Modify an existing group.': 'Create a new group.'}</Typography>
                <Divider />
            </Grid>
            <Grid item>
                <TextField
                    fullWidth
                    variant="outlined"
                    label="Group Title"
                    defaultValue={text}
                    onChange={event => setText(event.target.value)} 
                /> 
            </Grid>
            <Grid item>
                <Divider />
                <Typography variant="h6">Configure questions, parameters, and options.</Typography>
            </Grid>
            <Grid item>
                <Stepper nonLinear activeStep={activeStep} orientation="vertical">
                  {questions.map((x, idx) => (
                      <ActivitySelector activities={activities}>{JSON.stringify(x)}</ActivitySelector>
                  ))}
                  <Grid container direction="row" justify="flex-start" alignItems="center" spacing={2} style={{ margin: '8px 0px 0px -8px' }}>
                      <Fab
                        size="small"
                        color="primary"
                        onClick={() => {setQuestions(questions => [...questions, {}]); setActiveStep(questions.length)}}
                      >
                        <Icon fontSize="small">add_circle</Icon>
                      </Fab>
                      <Grid item>
                          <Typography variant="subtitle2">Add Activity</Typography>
                      </Grid>
                  </Grid>
                </Stepper>
            </Grid>
            <Grid container 
              direction="column" 
              alignItems="flex-end" 
              spacing={1} 
              style={{ position: 'fixed', bottom: 24, right: 24, width: 'auto' }}
            >
              {!!value &&
                <Grid item>
                  <Tooltip title="Duplicate this survey instrument and save it with a new title.">
                    <Fab 
                      color="primary" 
                      aria-label="Duplicate" 
                      variant="extended"
                      onClick={() => onSave({ 
                          id: undefined,
                          name: text, 
                          spec: 'lamp.survey',
                          schedule: [],
                          settings: questions,
                      }, true /* duplicate */)} 
                      disabled={!onSave || questions.length === 0 || !text || (value.name.trim() === text.trim())}
                    >
                      Duplicate
                      <span style={{ width: 8 }} />
                      <Icon>file_copy</Icon>
                    </Fab>
                  </Tooltip>
                </Grid>
              }
              <Grid item>
                <Tooltip title="Save this survey instrument.">
                  <Fab 
                    color="secondary" 
                    aria-label="Save" 
                    variant="extended"
                    onClick={() => onSave({ 
                        id: undefined,
                        name: text, 
                        spec: 'lamp.survey',
                        schedule: [],
                        settings: questions,
                    }, false /* overwrite */)} 
                    disabled={!onSave || questions.length === 0 || !text}
                  >
                    Save
                    <span style={{ width: 8 }} />
                    <Icon>save</Icon>
                  </Fab>
                </Tooltip>
              </Grid>
            </Grid>
        </Grid>
    )
}