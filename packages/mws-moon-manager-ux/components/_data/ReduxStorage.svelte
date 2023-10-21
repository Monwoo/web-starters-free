<script lang="ts">
  // 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
  // import * as  ReduxStorage from '../../stores/reduxStorage.ts';
  import * as  ReduxStorage from '../../stores/reduxStorage.mjs';

  export let stateUpdate;

  ReduxStorage.state.update((state) => {
    return {
      ...state,
      ...(stateUpdate || {})
    }
  })

  console.debug('Did update mws redux with : ', stateUpdate);
</script>