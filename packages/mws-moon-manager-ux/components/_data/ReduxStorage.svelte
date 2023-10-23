<script lang="ts">
    import { state, stateUpdate as _stateUpdate } from "../../stores/reduxStorage.mjs";

  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // import * as  ReduxStorage from '../../stores/reduxStorage.ts';

  export let stateUpdate;

  _stateUpdate(state, stateUpdate || {});

  console.debug('Did update MWS ReduxStorage with : ', stateUpdate);
</script>