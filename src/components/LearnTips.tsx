// Core Imports
import React, { useState } from "react"
import {
  Typography,
  makeStyles,
  Box,
  Grid,
  IconButton,
  useTheme,
  Container,
  AppBar,
  Toolbar,
  Icon,
  CardMedia,
  CardContent,
  useMediaQuery,
  CardActionArea,
  Card,
  Link,
  Fab
} from "@material-ui/core"
import ResponsiveDialog from "./ResponsiveDialog"
import ChevronRightIcon from "@material-ui/icons/ChevronRight"
import { ReactComponent as ThumbsUp } from "../icons/ThumbsUp.svg"
import { ReactComponent as ThumbsDown } from "../icons/ThumbsDown.svg"

const useStyles = makeStyles((theme) => ({
  topicon: {
    minWidth: 180,
    minHeight: 180,
    [theme.breakpoints.down("xs")]: {
      minWidth: 180,
    minHeight: 180,
    },
  },
  root2: {
    maxWidth: 345,
    margin: "16px",
    maxLength: 500,
  },
  media: {
    height: 200,
  },
  header: {
    background: "#FFF9E5",
    padding: "0 20px 20px 20px",
    [theme.breakpoints.up("sm")]: {
      textAlign: "center",
    },

    "& h2": {
      fontSize: 25,
      fontWeight: 600,
      color: "rgba(0, 0, 0, 0.75)",
    },
  },
  headerIcon: { textAlign: "center" },
  tipscontentarea: {
    padding: 20,
    "& h3": {
      fontWeight: "bold",
      fontSize: "16px",
      marginBottom: "15px",
    },
    "& p": {
      fontSize: "14px",
      lineHeight: "20px",
      color: "rgba(0, 0, 0, 0.75)",
    },
  },
  tipStyle: {
    background: "#FFF9E5",
    borderRadius: "10px",
    padding: "20px 20px 20px 20px",
    textAlign: "justify",
    margin: "20px auto 0px",
    "& h6": { fontSize: 16, fontWeight: 600, color: "rgba(0, 0, 0, 0.75)" },
  },
  toolbardashboard: {
    minHeight: 65,
    padding: "0 10px",
    "& h5": {
      color: "rgba(0, 0, 0, 0.75)",
      textAlign: "center",
      fontWeight: "600",
      fontSize: 18,
      width: "calc(100% - 96px)",
    },
  },
  backbtn: { 
    // paddingLeft: 0, paddingRight: 0 
  },
  rightArrow: { maxWidth: 50, padding: "15px 12px 11px 12px !important", "& svg": { color: "rgba(0, 0, 0, 0.5)" } },
  lineyellow: {
    background: "#FFD645",
    height: "3px",
  },
  linegreen: {
    background: "#65CEBF",
    height: "3px",
  },
  linered: {
    background: "#FF775B",
    height: "3px",
  },
  lineblue: {
    background: "#86B6FF",
    height: "3px",
  },
  likebtn: {
    fontStyle: "italic",
    padding: 6,
    margin: "0 5px",
    "& label": {
      position: "absolute",
      bottom: -18,
      fontSize: 12,
    },
  },
  active: {
    background: "#FFD645",
  },
  howFeel: { fontSize: 14, color: "rgba(0, 0, 0, 0.5)", fontStyle: "italic", textAlign: "center", marginBottom: 10 },
  btnyellow: {
    background: "#FFD645",
    borderRadius: "40px",
    minWidth: "200px",
    boxShadow: "0px 10px 15px rgba(255, 214, 69, 0.25)",
    lineHeight: "38px",
    marginTop: "15%",
    cursor: "pointer",
    textTransform: "capitalize",
    fontSize: "16px",
    color: "rgba(0, 0, 0, 0.75)",
    "&:hover": { background: "#cea000" },
  },
}))

export default function LearnTips({ ...props }) {
  const classes = useStyles()
  const [openDialog, setOpenDialog] = useState(false)
  const [title, setTitle] = useState(null)
  const [details, setDetails] = useState(null)
  const supportsSidebar = useMediaQuery(useTheme().breakpoints.up("md"))
  

  return (
    <Container>
      <Box>
        <Grid container direction="row" alignItems="stretch">
          {props.details.map((detail) =>
            props.type === 2 ? (
              <Grid item lg={6} sm={12} xs={12}>
                <Card className={classes.root2}>
                  <CardActionArea>
                    <CardMedia className={classes.media} image={detail.image} />
                    <CardContent>
                      <Typography gutterBottom variant="h5" component="h2">
                        {detail.text}
                        <br />
                        {detail.author}
                      </Typography>
                      {detail.link && <Link href={detail.link}>{detail.text}</Link>}
                    </CardContent>
                  </CardActionArea>
                </Card>
              </Grid>
            ) : (
              <Grid container direction="row" justify="center" alignItems="center">
                <Grid item lg={6} sm={12} xs={12}>
                  <Box
                    className={classes.tipStyle}
                    onClick={() => {
                      setOpenDialog(true)
                      setTitle(detail.title)
                      setDetails(detail.text)
                    }}
                  >
                    {supportsSidebar ? (
                      <div>
                        <Grid container spacing={3}>
                          <Grid item xs>
                            <Typography variant="h6">{detail.title}</Typography>
                          </Grid>
                          <Grid item xs justify="center" className={classes.rightArrow}>
                            <ChevronRightIcon />
                          </Grid>
                        </Grid>
                      </div>
                    ) : (
                      <Typography variant="h6">{detail.title}</Typography>
                    )}
                  </Box>
                </Grid>
              </Grid>
            )
          )}{" "}
        </Grid>
      </Box>
      <ResponsiveDialog
        transient={false}
        animate
        fullScreen
        open={openDialog}
        onClose={() => {
          setOpenDialog(false)
        }}
      >
        <AppBar position="static" style={{ background: "#FFF9E5", boxShadow: "none" }}>
          <Toolbar className={classes.toolbardashboard}>
            <IconButton
              onClick={() => setOpenDialog(false)}
              color="default"
              className={classes.backbtn}
              aria-label="Menu"
            >
              <Icon>arrow_back</Icon>
            </IconButton>
          </Toolbar>
        </AppBar>

        <Box className={classes.header}>
          <Box width={1} className={classes.headerIcon}>
            {props.icon}
          </Box>
          <Typography variant="caption">Tip</Typography>
          <Typography variant="h2">{title}</Typography>
        </Box>
        <Grid
  container
  direction="row"
  justify="center"
  alignItems="flex-start"
>
      <Grid item lg={4} sm={10} xs={12}>
        <CardContent className={classes.tipscontentarea}>
          <Typography variant="body2" color="textSecondary" component="p">
            {details}
          </Typography>
          <Box mt={4} mb={3}>
          <Grid container direction="row" justify="center" alignItems="center">
            <Grid container spacing={0} xs={4} md={4} lg={2}>
              <Grid item xs={3} className={classes.lineyellow}></Grid>
              <Grid item xs={3} className={classes.linegreen}></Grid>
              <Grid item xs={3} className={classes.linered}></Grid>
              <Grid item xs={3} className={classes.lineblue}></Grid>
            </Grid>
          </Grid>
        </Box>{" "}
        <Box className={classes.howFeel}>Was this helpful today?</Box>
        <Box textAlign="center">
          <IconButton
           
            className={classes.likebtn}
          >
            <ThumbsUp />
            <label>Yes</label>
          </IconButton>
          <IconButton
           
           className={classes.likebtn}
          >
            <ThumbsDown />
            <label>No</label>
          </IconButton>
        </Box>
        <Box textAlign="center">
          <Fab variant="extended" color="primary" className={classes.btnyellow}>
          Mark complete
          </Fab>
        </Box>
        </CardContent>
        </Grid>
        </Grid>
      </ResponsiveDialog>
    </Container>
  )
}
